<?php

declare(strict_types=1);

namespace Bookando\Core\Integrations\VideoConference;

use Bookando\Core\Service\ActivityLogger;

/**
 * Class GoogleMeetIntegration
 *
 * Google Meet Integration for Online Courses/Appointments.
 * Uses Google Calendar API to create events with Google Meet conferencing.
 *
 * Note: Requires Google Calendar API access and OAuth2 credentials.
 *
 * @package Bookando\Core\Integrations\VideoConference
 */
class GoogleMeetIntegration
{
    private const API_BASE_URL = 'https://www.googleapis.com/calendar/v3';
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $refreshToken = null;
    private ?string $accessToken = null;
    private ?int $tenantId = null;

    /**
     * GoogleMeetIntegration constructor.
     *
     * @param array    $config Configuration array
     * @param int|null $tenantId Tenant ID
     */
    public function __construct(array $config = [], ?int $tenantId = null)
    {
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->refreshToken = $config['refresh_token'] ?? null;
        $this->tenantId = $tenantId;
    }

    /**
     * Create a Google Meet meeting (via Calendar Event)
     *
     * @param array $params Meeting parameters
     *                      - summary: string (meeting title)
     *                      - description: string (meeting description)
     *                      - start_time: string (ISO 8601 format)
     *                      - end_time: string (ISO 8601 format)
     *                      - timezone: string (e.g., 'Europe/Zurich')
     *                      - attendees: array of email addresses
     *
     * @return array Meeting details including join URL
     * @throws \Exception
     */
    public function createMeeting(array $params): array
    {
        $this->authenticate();

        $calendarId = $params['calendar_id'] ?? 'primary';

        $eventData = [
            'summary' => $params['summary'] ?? 'Bookando Meeting',
            'description' => $params['description'] ?? '',
            'start' => [
                'dateTime' => $params['start_time'],
                'timeZone' => $params['timezone'] ?? 'UTC',
            ],
            'end' => [
                'dateTime' => $params['end_time'],
                'timeZone' => $params['timezone'] ?? 'UTC',
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid('bookando_'),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet', // Google Meet
                    ],
                ],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60], // 1 day before
                    ['method' => 'popup', 'minutes' => 10],      // 10 minutes before
                ],
            ],
        ];

        // Add attendees
        if (!empty($params['attendees']) && is_array($params['attendees'])) {
            $eventData['attendees'] = array_map(
                fn($email) => ['email' => $email],
                $params['attendees']
            );
        }

        $response = $this->makeRequest(
            'POST',
            "/calendars/{$calendarId}/events?conferenceDataVersion=1",
            $eventData
        );

        $meetLink = $response['hangoutLink'] ?? $response['conferenceData']['entryPoints'][0]['uri'] ?? null;

        ActivityLogger::log(
            'google_meet_created',
            'Google Meet meeting created',
            [
                'event_id' => $response['id'] ?? null,
                'summary' => $eventData['summary'],
            ],
            'INFO',
            $this->tenantId,
            'integrations'
        );

        return [
            'success' => true,
            'meeting_id' => $response['id'],
            'event_id' => $response['id'],
            'join_url' => $meetLink,
            'html_link' => $response['htmlLink'] ?? null,
            'provider' => 'google_meet',
        ];
    }

    /**
     * Get meeting/event details
     *
     * @param string $eventId Google Calendar event ID
     * @param string $calendarId Calendar ID (default: 'primary')
     *
     * @return array Event details
     * @throws \Exception
     */
    public function getMeeting(string $eventId, string $calendarId = 'primary'): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', "/calendars/{$calendarId}/events/{$eventId}");

        $meetLink = $response['hangoutLink'] ?? $response['conferenceData']['entryPoints'][0]['uri'] ?? null;

        return [
            'success' => true,
            'event_id' => $response['id'],
            'summary' => $response['summary'] ?? '',
            'start_time' => $response['start']['dateTime'] ?? $response['start']['date'] ?? null,
            'end_time' => $response['end']['dateTime'] ?? $response['end']['date'] ?? null,
            'join_url' => $meetLink,
            'status' => $response['status'] ?? 'confirmed',
        ];
    }

    /**
     * Update a meeting/event
     *
     * @param string $eventId Google Calendar event ID
     * @param array  $params Update parameters
     * @param string $calendarId Calendar ID (default: 'primary')
     *
     * @return array Update result
     * @throws \Exception
     */
    public function updateMeeting(string $eventId, array $params, string $calendarId = 'primary'): array
    {
        $this->authenticate();

        $updateData = [];

        if (isset($params['summary'])) {
            $updateData['summary'] = $params['summary'];
        }

        if (isset($params['description'])) {
            $updateData['description'] = $params['description'];
        }

        if (isset($params['start_time'])) {
            $updateData['start'] = [
                'dateTime' => $params['start_time'],
                'timeZone' => $params['timezone'] ?? 'UTC',
            ];
        }

        if (isset($params['end_time'])) {
            $updateData['end'] = [
                'dateTime' => $params['end_time'],
                'timeZone' => $params['timezone'] ?? 'UTC',
            ];
        }

        $this->makeRequest('PATCH', "/calendars/{$calendarId}/events/{$eventId}", $updateData);

        ActivityLogger::log(
            'google_meet_updated',
            'Google Meet meeting updated',
            ['event_id' => $eventId],
            'INFO',
            $this->tenantId,
            'integrations'
        );

        return ['success' => true, 'event_id' => $eventId];
    }

    /**
     * Delete a meeting/event
     *
     * @param string $eventId Google Calendar event ID
     * @param string $calendarId Calendar ID (default: 'primary')
     *
     * @return array Delete result
     * @throws \Exception
     */
    public function deleteMeeting(string $eventId, string $calendarId = 'primary'): array
    {
        $this->authenticate();

        $this->makeRequest('DELETE', "/calendars/{$calendarId}/events/{$eventId}");

        ActivityLogger::log(
            'google_meet_deleted',
            'Google Meet meeting deleted',
            ['event_id' => $eventId],
            'INFO',
            $this->tenantId,
            'integrations'
        );

        return ['success' => true, 'event_id' => $eventId];
    }

    /**
     * Test connection to Google Calendar API
     *
     * @return array Test result
     */
    public function testConnection(): array
    {
        try {
            $this->authenticate();

            $response = $this->makeRequest('GET', '/users/me/calendarList');

            return [
                'success' => true,
                'message' => 'Successfully connected to Google Calendar',
                'calendars_count' => count($response['items'] ?? []),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get OAuth2 authorization URL
     *
     * @param string $redirectUri Redirect URI after authorization
     * @param string $state Optional state parameter
     *
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $redirectUri, string $state = ''): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        if (!empty($state)) {
            $params['state'] = $state;
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for refresh token
     *
     * @param string $code Authorization code
     * @param string $redirectUri Redirect URI used in authorization
     *
     * @return array Token data
     * @throws \Exception
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = wp_remote_post(self::OAUTH_TOKEN_URL, [
            'body' => [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
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
     * Authenticate with Google using OAuth2 refresh token
     *
     * @return void
     * @throws \Exception
     */
    private function authenticate(): void
    {
        // Check cached access token
        $cached = get_transient('bookando_google_meet_token_' . ($this->tenantId ?? 'global'));
        if ($cached) {
            $this->accessToken = $cached;
            return;
        }

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            throw new \Exception('Google Meet credentials not configured');
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
        $expiresIn = $data['expires_in'] ?? 3600;
        set_transient('bookando_google_meet_token_' . ($this->tenantId ?? 'global'), $this->accessToken, $expiresIn - 60);
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

        // DELETE returns 204 No Content
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
     * Get configuration fields
     *
     * @return array Configuration fields
     */
    public static function getConfigurationFields(): array
    {
        return [
            'client_id' => [
                'type' => 'text',
                'label' => 'Client ID',
                'required' => true,
                'description' => 'Your Google OAuth2 Client ID from Google Cloud Console',
            ],
            'client_secret' => [
                'type' => 'password',
                'label' => 'Client Secret',
                'required' => true,
                'description' => 'Your Google OAuth2 Client Secret',
            ],
            'refresh_token' => [
                'type' => 'password',
                'label' => 'Refresh Token',
                'required' => true,
                'description' => 'OAuth2 Refresh Token (obtained via OAuth flow)',
            ],
        ];
    }
}
