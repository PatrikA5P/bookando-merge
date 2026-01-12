<?php

declare(strict_types=1);

namespace Bookando\Core\Integrations\VideoConference;

use Bookando\Core\Service\ActivityLogger;

/**
 * Class ZoomIntegration
 *
 * Zoom Video Conferencing Integration for Online Courses/Appointments.
 * Uses Zoom Server-to-Server OAuth and Meetings API.
 *
 * @package Bookando\Core\Integrations\VideoConference
 */
class ZoomIntegration
{
    private const API_BASE_URL = 'https://api.zoom.us/v2';
    private const OAUTH_TOKEN_URL = 'https://zoom.us/oauth/token';

    private ?string $accountId = null;
    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $accessToken = null;
    private ?int $tenantId = null;

    /**
     * ZoomIntegration constructor.
     *
     * @param array    $config Configuration array
     * @param int|null $tenantId Tenant ID
     */
    public function __construct(array $config = [], ?int $tenantId = null)
    {
        $this->accountId = $config['account_id'] ?? null;
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->tenantId = $tenantId;
    }

    /**
     * Create a Zoom meeting
     *
     * @param array $params Meeting parameters
     *                      - topic: string (meeting name)
     *                      - start_time: string (ISO 8601 format)
     *                      - duration: int (minutes)
     *                      - timezone: string (e.g., 'Europe/Zurich')
     *                      - password: string (optional)
     *                      - waiting_room: bool
     *                      - host_video: bool
     *                      - participant_video: bool
     *                      - auto_recording: string ('none', 'local', 'cloud')
     *
     * @return array Meeting details including join URL
     * @throws \Exception
     */
    public function createMeeting(array $params): array
    {
        $this->authenticate();

        $userId = $params['user_id'] ?? 'me'; // 'me' uses authenticated account

        $meetingData = [
            'topic' => $params['topic'] ?? 'Bookando Meeting',
            'type' => 2, // Scheduled meeting
            'start_time' => $params['start_time'],
            'duration' => $params['duration'] ?? 60,
            'timezone' => $params['timezone'] ?? 'UTC',
            'settings' => [
                'host_video' => $params['host_video'] ?? true,
                'participant_video' => $params['participant_video'] ?? true,
                'join_before_host' => $params['join_before_host'] ?? false,
                'mute_upon_entry' => $params['mute_upon_entry'] ?? false,
                'watermark' => false,
                'use_pmi' => false,
                'approval_type' => 2, // No registration required
                'audio' => 'both', // Telephone and VoIP
                'auto_recording' => $params['auto_recording'] ?? 'none',
                'waiting_room' => $params['waiting_room'] ?? true,
            ],
        ];

        // Add password if specified
        if (!empty($params['password'])) {
            $meetingData['password'] = $params['password'];
        }

        // Add agenda/description
        if (!empty($params['agenda'])) {
            $meetingData['agenda'] = $params['agenda'];
        }

        $response = $this->makeRequest('POST', "/users/{$userId}/meetings", $meetingData);

        ActivityLogger::log(
            'zoom_meeting_created',
            'Zoom meeting created',
            [
                'meeting_id' => $response['id'] ?? null,
                'topic' => $meetingData['topic'],
            ],
            'INFO',
            $this->tenantId,
            'integrations'
        );

        return [
            'success' => true,
            'meeting_id' => (string) $response['id'],
            'join_url' => $response['join_url'],
            'start_url' => $response['start_url'], // For host
            'password' => $response['password'] ?? null,
            'meeting_number' => $response['id'],
            'provider' => 'zoom',
        ];
    }

    /**
     * Get meeting details
     *
     * @param string $meetingId Zoom meeting ID
     *
     * @return array Meeting details
     * @throws \Exception
     */
    public function getMeeting(string $meetingId): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', "/meetings/{$meetingId}");

        return [
            'success' => true,
            'meeting_id' => (string) $response['id'],
            'topic' => $response['topic'],
            'start_time' => $response['start_time'],
            'duration' => $response['duration'],
            'timezone' => $response['timezone'],
            'join_url' => $response['join_url'],
            'status' => $response['status'] ?? 'waiting',
        ];
    }

    /**
     * Update a meeting
     *
     * @param string $meetingId Zoom meeting ID
     * @param array  $params Update parameters
     *
     * @return array Update result
     * @throws \Exception
     */
    public function updateMeeting(string $meetingId, array $params): array
    {
        $this->authenticate();

        $updateData = [];

        if (isset($params['topic'])) {
            $updateData['topic'] = $params['topic'];
        }

        if (isset($params['start_time'])) {
            $updateData['start_time'] = $params['start_time'];
        }

        if (isset($params['duration'])) {
            $updateData['duration'] = $params['duration'];
        }

        if (isset($params['timezone'])) {
            $updateData['timezone'] = $params['timezone'];
        }

        $this->makeRequest('PATCH', "/meetings/{$meetingId}", $updateData);

        ActivityLogger::log(
            'zoom_meeting_updated',
            'Zoom meeting updated',
            ['meeting_id' => $meetingId],
            'INFO',
            $this->tenantId,
            'integrations'
        );

        return ['success' => true, 'meeting_id' => $meetingId];
    }

    /**
     * Delete a meeting
     *
     * @param string $meetingId Zoom meeting ID
     *
     * @return array Delete result
     * @throws \Exception
     */
    public function deleteMeeting(string $meetingId): array
    {
        $this->authenticate();

        $this->makeRequest('DELETE', "/meetings/{$meetingId}");

        ActivityLogger::log(
            'zoom_meeting_deleted',
            'Zoom meeting deleted',
            ['meeting_id' => $meetingId],
            'INFO',
            $this->tenantId,
            'integrations'
        );

        return ['success' => true, 'meeting_id' => $meetingId];
    }

    /**
     * Get meeting participants
     *
     * @param string $meetingId Zoom meeting ID
     *
     * @return array Participants list
     * @throws \Exception
     */
    public function getParticipants(string $meetingId): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', "/past_meetings/{$meetingId}/participants");

        return [
            'success' => true,
            'participants' => $response['participants'] ?? [],
            'total' => $response['total_records'] ?? 0,
        ];
    }

    /**
     * Test connection to Zoom API
     *
     * @return array Test result
     */
    public function testConnection(): array
    {
        try {
            $this->authenticate();

            $response = $this->makeRequest('GET', '/users/me');

            return [
                'success' => true,
                'message' => 'Successfully connected to Zoom',
                'account_id' => $response['account_id'] ?? null,
                'email' => $response['email'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate with Zoom using Server-to-Server OAuth
     *
     * @return void
     * @throws \Exception
     */
    private function authenticate(): void
    {
        if ($this->accessToken !== null) {
            return; // Already authenticated
        }

        if (empty($this->accountId) || empty($this->clientId) || empty($this->clientSecret)) {
            throw new \Exception('Zoom credentials not configured');
        }

        $url = self::OAUTH_TOKEN_URL . '?grant_type=account_credentials&account_id=' . $this->accountId;

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Zoom OAuth failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['access_token'])) {
            throw new \Exception('Failed to obtain Zoom access token');
        }

        $this->accessToken = $data['access_token'];

        // Cache token (valid for 1 hour)
        set_transient('bookando_zoom_token_' . ($this->tenantId ?? 'global'), $this->accessToken, 3600);
    }

    /**
     * Make API request to Zoom
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
            throw new \Exception('Zoom API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // DELETE returns 204 No Content
        if ($statusCode === 204) {
            return ['success' => true];
        }

        $result = json_decode($body, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $errorMsg = $result['message'] ?? 'Unknown error';
            throw new \Exception("Zoom API error (HTTP {$statusCode}): {$errorMsg}");
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
            'account_id' => [
                'type' => 'text',
                'label' => 'Account ID',
                'required' => true,
                'description' => 'Your Zoom Account ID from Server-to-Server OAuth app',
            ],
            'client_id' => [
                'type' => 'text',
                'label' => 'Client ID',
                'required' => true,
                'description' => 'Your Zoom Client ID',
            ],
            'client_secret' => [
                'type' => 'password',
                'label' => 'Client Secret',
                'required' => true,
                'description' => 'Your Zoom Client Secret',
            ],
        ];
    }
}
