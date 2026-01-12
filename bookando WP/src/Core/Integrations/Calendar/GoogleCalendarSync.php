<?php

declare(strict_types=1);

namespace Bookando\Core\Integrations\Calendar;

use Bookando\Core\Service\ActivityLogger;

/**
 * Class GoogleCalendarSync
 *
 * Bidirectional Google Calendar synchronization.
 * - Read busy times to prevent double-booking
 * - Write Bookando appointments to Google Calendar
 *
 * @package Bookando\Core\Integrations\Calendar
 */
class GoogleCalendarSync
{
    private const API_BASE_URL = 'https://www.googleapis.com/calendar/v3';
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPES = [
        'https://www.googleapis.com/auth/calendar.readonly',
        'https://www.googleapis.com/auth/calendar.events',
    ];

    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $refreshToken = null;
    private ?string $accessToken = null;
    private ?int $employeeId = null;
    private ?int $tenantId = null;

    /**
     * GoogleCalendarSync constructor.
     *
     * @param array    $config Configuration array
     * @param int|null $employeeId Employee ID
     * @param int|null $tenantId Tenant ID
     */
    public function __construct(array $config = [], ?int $employeeId = null, ?int $tenantId = null)
    {
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->refreshToken = $config['refresh_token'] ?? null;
        $this->employeeId = $employeeId;
        $this->tenantId = $tenantId;
    }

    /**
     * Get OAuth2 authorization URL for connecting calendar
     *
     * @param string $redirectUri Redirect URI
     * @param int    $employeeId Employee ID
     * @param string $mode 'ro' (read-only) or 'wb' (read-write)
     *
     * @return string Authorization URL
     */
    public static function getAuthUrl(string $redirectUri, int $employeeId, string $mode = 'ro'): string
    {
        $config = self::getGlobalConfig();

        $scope = $mode === 'wb'
            ? implode(' ', self::SCOPES)
            : self::SCOPES[0]; // Read-only

        $params = [
            'client_id' => $config['client_id'] ?? '',
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => base64_encode(json_encode(['employee_id' => $employeeId, 'mode' => $mode, 'provider' => 'google'])),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens
     *
     * @param string $code Authorization code
     * @param string $redirectUri Redirect URI
     *
     * @return array Token data [access_token, refresh_token, expires_in]
     * @throws \Exception
     */
    public static function exchangeCode(string $code, string $redirectUri): array
    {
        $config = self::getGlobalConfig();

        $response = wp_remote_post(self::OAUTH_TOKEN_URL, [
            'body' => [
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Token exchange failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['refresh_token'])) {
            throw new \Exception('Failed to obtain refresh token');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
        ];
    }

    /**
     * Get list of user's calendars
     *
     * @return array List of calendars
     * @throws \Exception
     */
    public function getCalendarList(): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', '/users/me/calendarList');

        $calendars = [];
        foreach ($response['items'] ?? [] as $calendar) {
            $calendars[] = [
                'calendar_id' => $calendar['id'],
                'label' => $calendar['summary'] ?? $calendar['id'],
                'subLabel' => $calendar['description'] ?? '',
                'is_primary' => ($calendar['primary'] ?? false),
                'color' => $calendar['backgroundColor'] ?? null,
            ];
        }

        return $calendars;
    }

    /**
     * Get busy times from Google Calendar (for conflict detection)
     *
     * @param string $calendarId Calendar ID ('primary' or specific ID)
     * @param string $timeMin Start time (ISO 8601)
     * @param string $timeMax End time (ISO 8601)
     *
     * @return array Busy time slots
     * @throws \Exception
     */
    public function getFreeBusy(string $calendarId, string $timeMin, string $timeMax): array
    {
        $this->authenticate();

        $requestBody = [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'items' => [
                ['id' => $calendarId],
            ],
        ];

        $response = $this->makeRequest('POST', '/freeBusy', $requestBody);

        $busySlots = [];
        foreach ($response['calendars'][$calendarId]['busy'] ?? [] as $slot) {
            $busySlots[] = [
                'start' => $slot['start'],
                'end' => $slot['end'],
            ];
        }

        return $busySlots;
    }

    /**
     * Create event in Google Calendar
     *
     * @param string $calendarId Calendar ID
     * @param array  $eventData Event data
     *                          - summary: string (title)
     *                          - description: string
     *                          - start: string (ISO 8601)
     *                          - end: string (ISO 8601)
     *                          - timezone: string
     *                          - attendees: array (emails)
     *
     * @return array Created event with event_id
     * @throws \Exception
     */
    public function createEvent(string $calendarId, array $eventData): array
    {
        $this->authenticate();

        $event = [
            'summary' => $eventData['summary'] ?? 'Bookando Appointment',
            'description' => $eventData['description'] ?? '',
            'start' => [
                'dateTime' => $eventData['start'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ],
            'end' => [
                'dateTime' => $eventData['end'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ],
        ];

        if (!empty($eventData['location'])) {
            $event['location'] = $eventData['location'];
        }

        if (!empty($eventData['attendees']) && is_array($eventData['attendees'])) {
            $event['attendees'] = array_map(fn($email) => ['email' => $email], $eventData['attendees']);
        }

        // Add Bookando metadata
        $event['extendedProperties'] = [
            'private' => [
                'bookando_appointment_id' => $eventData['appointment_id'] ?? '',
                'bookando_source' => 'bookando',
            ],
        ];

        $response = $this->makeRequest('POST', "/calendars/{$calendarId}/events", $event);

        ActivityLogger::log(
            'google_calendar_event_created',
            'Event created in Google Calendar',
            [
                'event_id' => $response['id'] ?? null,
                'employee_id' => $this->employeeId,
            ],
            'INFO',
            $this->tenantId,
            'calendar_sync'
        );

        return [
            'event_id' => $response['id'],
            'html_link' => $response['htmlLink'] ?? null,
        ];
    }

    /**
     * Update event in Google Calendar
     *
     * @param string $calendarId Calendar ID
     * @param string $eventId Event ID
     * @param array  $eventData Updated event data
     *
     * @return array Updated event
     * @throws \Exception
     */
    public function updateEvent(string $calendarId, string $eventId, array $eventData): array
    {
        $this->authenticate();

        $updateData = [];

        if (isset($eventData['summary'])) {
            $updateData['summary'] = $eventData['summary'];
        }

        if (isset($eventData['description'])) {
            $updateData['description'] = $eventData['description'];
        }

        if (isset($eventData['start'])) {
            $updateData['start'] = [
                'dateTime' => $eventData['start'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ];
        }

        if (isset($eventData['end'])) {
            $updateData['end'] = [
                'dateTime' => $eventData['end'],
                'timeZone' => $eventData['timezone'] ?? 'UTC',
            ];
        }

        $response = $this->makeRequest('PATCH', "/calendars/{$calendarId}/events/{$eventId}", $updateData);

        ActivityLogger::log(
            'google_calendar_event_updated',
            'Event updated in Google Calendar',
            ['event_id' => $eventId, 'employee_id' => $this->employeeId],
            'INFO',
            $this->tenantId,
            'calendar_sync'
        );

        return ['event_id' => $response['id']];
    }

    /**
     * Delete event from Google Calendar
     *
     * @param string $calendarId Calendar ID
     * @param string $eventId Event ID
     *
     * @return array Delete result
     * @throws \Exception
     */
    public function deleteEvent(string $calendarId, string $eventId): array
    {
        $this->authenticate();

        $this->makeRequest('DELETE', "/calendars/{$calendarId}/events/{$eventId}");

        ActivityLogger::log(
            'google_calendar_event_deleted',
            'Event deleted from Google Calendar',
            ['event_id' => $eventId, 'employee_id' => $this->employeeId],
            'INFO',
            $this->tenantId,
            'calendar_sync'
        );

        return ['success' => true];
    }

    /**
     * Authenticate with Google using refresh token
     *
     * @return void
     * @throws \Exception
     */
    private function authenticate(): void
    {
        // Check cached token
        $cacheKey = 'bookando_google_cal_token_' . ($this->employeeId ?? 'global');
        $cached = get_transient($cacheKey);
        if ($cached) {
            $this->accessToken = $cached;
            return;
        }

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            throw new \Exception('Google Calendar credentials not configured');
        }

        $response = wp_remote_post(self::OAUTH_TOKEN_URL, [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Google OAuth failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['access_token'])) {
            throw new \Exception('Failed to obtain Google access token');
        }

        $this->accessToken = $data['access_token'];

        // Cache token
        set_transient($cacheKey, $this->accessToken, ($data['expires_in'] ?? 3600) - 60);
    }

    /**
     * Make API request to Google Calendar
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array  $data Request data
     *
     * @return array Response data
     * @throws \Exception
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        if ($this->accessToken === null) {
            throw new \Exception('Not authenticated');
        }

        $url = self::API_BASE_URL . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($data) && in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('Google Calendar API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($statusCode === 204) {
            return ['success' => true];
        }

        $result = json_decode($body, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $errorMsg = $result['error']['message'] ?? 'Unknown error';
            throw new \Exception("Google Calendar API error (HTTP {$statusCode}): {$errorMsg}");
        }

        return $result ?? [];
    }

    /**
     * Get global Google Calendar configuration
     *
     * @return array Configuration
     */
    private static function getGlobalConfig(): array
    {
        return get_option('bookando_google_calendar_config', []);
    }
}
