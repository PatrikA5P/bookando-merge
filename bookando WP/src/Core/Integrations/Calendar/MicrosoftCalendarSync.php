<?php

declare(strict_types=1);

namespace Bookando\Core\Integrations\Calendar;

use Bookando\Core\Service\ActivityLogger;

/**
 * Class MicrosoftCalendarSync
 *
 * Microsoft Outlook/Office 365 Calendar Sync via Microsoft Graph API.
 *
 * @package Bookando\Core\Integrations\Calendar
 */
class MicrosoftCalendarSync
{
    private const API_BASE_URL = 'https://graph.microsoft.com/v1.0';
    private const OAUTH_TOKEN_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    private const SCOPES = ['Calendars.ReadWrite', 'Calendars.Read'];

    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $refreshToken = null;
    private ?string $accessToken = null;
    private ?int $employeeId = null;
    private ?int $tenantId = null;

    public function __construct(array $config = [], ?int $employeeId = null, ?int $tenantId = null)
    {
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->refreshToken = $config['refresh_token'] ?? null;
        $this->employeeId = $employeeId;
        $this->tenantId = $tenantId;
    }

    public static function getAuthUrl(string $redirectUri, int $employeeId, string $mode = 'ro'): string
    {
        $config = get_option('bookando_microsoft_calendar_config', []);
        $scope = 'offline_access ' . implode(' ', self::SCOPES);

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query([
            'client_id' => $config['client_id'] ?? '',
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'response_mode' => 'query',
            'state' => base64_encode(json_encode(['employee_id' => $employeeId, 'mode' => $mode, 'provider' => 'microsoft'])),
        ]);
    }

    public static function exchangeCode(string $code, string $redirectUri): array
    {
        $config = get_option('bookando_microsoft_calendar_config', []);

        $response = wp_remote_post(self::OAUTH_TOKEN_URL, [
            'body' => [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Token exchange failed: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
        ];
    }

    public function getCalendarList(): array
    {
        $this->authenticate();
        $response = $this->makeRequest('GET', '/me/calendars');

        return array_map(fn($cal) => [
            'calendar_id' => $cal['id'],
            'label' => $cal['name'],
            'subLabel' => $cal['owner']['name'] ?? '',
            'is_primary' => ($cal['isDefaultCalendar'] ?? false),
        ], $response['value'] ?? []);
    }

    public function createEvent(string $calendarId, array $eventData): array
    {
        $this->authenticate();

        $event = [
            'subject' => $eventData['summary'] ?? 'Bookando Appointment',
            'body' => ['contentType' => 'HTML', 'content' => $eventData['description'] ?? ''],
            'start' => ['dateTime' => $eventData['start'], 'timeZone' => $eventData['timezone'] ?? 'UTC'],
            'end' => ['dateTime' => $eventData['end'], 'timeZone' => $eventData['timezone'] ?? 'UTC'],
        ];

        $response = $this->makeRequest('POST', "/me/calendars/{$calendarId}/events", $event);

        ActivityLogger::log('microsoft_calendar_event_created', 'Event created', ['event_id' => $response['id'] ?? null], 'INFO', $this->tenantId, 'calendar_sync');

        return ['event_id' => $response['id'], 'web_link' => $response['webLink'] ?? null];
    }

    public function deleteEvent(string $calendarId, string $eventId): array
    {
        $this->authenticate();
        $this->makeRequest('DELETE', "/me/events/{$eventId}");
        ActivityLogger::log('microsoft_calendar_event_deleted', 'Event deleted', ['event_id' => $eventId], 'INFO', $this->tenantId, 'calendar_sync');
        return ['success' => true];
    }

    private function authenticate(): void
    {
        $cached = get_transient('bookando_ms_cal_token_' . ($this->employeeId ?? 'global'));
        if ($cached) {
            $this->accessToken = $cached;
            return;
        }

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            throw new \Exception('Microsoft Calendar credentials not configured');
        }

        $response = wp_remote_post(self::OAUTH_TOKEN_URL, [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Microsoft OAuth failed');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $this->accessToken = $data['access_token'];
        set_transient('bookando_ms_cal_token_' . ($this->employeeId ?? 'global'), $this->accessToken, ($data['expires_in'] ?? 3600) - 60);
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = self::API_BASE_URL . $endpoint;
        $args = [
            'method' => $method,
            'headers' => ['Authorization' => 'Bearer ' . $this->accessToken, 'Content-Type' => 'application/json'],
            'timeout' => 30,
        ];

        if (!empty($data) && in_array($method, ['POST', 'PATCH'], true)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('Microsoft Graph API request failed');
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode === 204) {
            return ['success' => true];
        }

        return json_decode(wp_remote_retrieve_body($response), true) ?? [];
    }
}
