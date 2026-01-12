<?php

declare(strict_types=1);

namespace Bookando\Core\Integrations\Calendar;

use Bookando\Core\Service\ActivityLogger;

/**
 * Class AppleCalendarSync
 *
 * Apple/iCloud Calendar Sync via ICS (iCalendar) feed.
 * Read-only integration for busy time detection.
 *
 * @package Bookando\Core\Integrations\Calendar
 */
class AppleCalendarSync
{
    private ?string $icsUrl = null;
    private ?int $employeeId = null;
    private ?int $tenantId = null;

    public function __construct(array $config = [], ?int $employeeId = null, ?int $tenantId = null)
    {
        $this->icsUrl = $config['ics_url'] ?? null;
        $this->employeeId = $employeeId;
        $this->tenantId = $tenantId;
    }

    /**
     * Get busy times from ICS feed
     *
     * @param string $timeMin Start time (ISO 8601)
     * @param string $timeMax End time (ISO 8601)
     *
     * @return array Busy time slots
     * @throws \Exception
     */
    public function getFreeBusy(string $timeMin, string $timeMax): array
    {
        if (empty($this->icsUrl)) {
            throw new \Exception('ICS URL not configured');
        }

        // Convert webcal:// to http://
        $url = str_replace('webcal://', 'https://', $this->icsUrl);

        $response = wp_remote_get($url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            throw new \Exception('Failed to fetch ICS feed: ' . $response->get_error_message());
        }

        $icsData = wp_remote_retrieve_body($response);

        // Parse ICS data
        $events = $this->parseICS($icsData, $timeMin, $timeMax);

        ActivityLogger::log(
            'apple_calendar_sync',
            'ICS feed synced',
            ['events_found' => count($events)],
            'INFO',
            $this->tenantId,
            'calendar_sync'
        );

        return $events;
    }

    /**
     * Parse ICS data and extract events within time range
     *
     * @param string $icsData ICS content
     * @param string $timeMin Start time
     * @param string $timeMax End time
     *
     * @return array Events
     */
    private function parseICS(string $icsData, string $timeMin, string $timeMax): array
    {
        $events = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $icsData));

        $inEvent = false;
        $currentEvent = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                $inEvent = false;

                // Check if event is in time range
                if (!empty($currentEvent['start']) && !empty($currentEvent['end'])) {
                    $start = strtotime($currentEvent['start']);
                    $end = strtotime($currentEvent['end']);
                    $rangeStart = strtotime($timeMin);
                    $rangeEnd = strtotime($timeMax);

                    // Event overlaps with time range
                    if ($start < $rangeEnd && $end > $rangeStart) {
                        $events[] = [
                            'start' => date('c', $start),
                            'end' => date('c', $end),
                            'summary' => $currentEvent['summary'] ?? 'Busy',
                        ];
                    }
                }
                continue;
            }

            if ($inEvent) {
                if (strpos($line, 'DTSTART') === 0) {
                    $currentEvent['start'] = $this->parseICSDate($line);
                } elseif (strpos($line, 'DTEND') === 0) {
                    $currentEvent['end'] = $this->parseICSDate($line);
                } elseif (strpos($line, 'SUMMARY:') === 0) {
                    $currentEvent['summary'] = substr($line, 8);
                }
            }
        }

        return $events;
    }

    /**
     * Parse ICS date format
     *
     * @param string $line ICS date line
     *
     * @return string|null ISO 8601 date
     */
    private function parseICSDate(string $line): ?string
    {
        // Extract date value (after colon)
        if (preg_match('/:(\d{8}T\d{6}Z?)/', $line, $matches)) {
            $dateStr = $matches[1];

            // Parse YYYYMMDDTHHMMSS format
            $year = substr($dateStr, 0, 4);
            $month = substr($dateStr, 4, 2);
            $day = substr($dateStr, 6, 2);
            $hour = substr($dateStr, 9, 2);
            $minute = substr($dateStr, 11, 2);
            $second = substr($dateStr, 13, 2);

            return "{$year}-{$month}-{$day}T{$hour}:{$minute}:{$second}Z";
        }

        return null;
    }

    /**
     * Test ICS feed connection
     *
     * @return array Test result
     */
    public function testConnection(): array
    {
        try {
            if (empty($this->icsUrl)) {
                return ['success' => false, 'message' => 'ICS URL not configured'];
            }

            $url = str_replace('webcal://', 'https://', $this->icsUrl);
            $response = wp_remote_get($url, ['timeout' => 10]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => 'Failed to fetch ICS feed'];
            }

            $body = wp_remote_retrieve_body($response);

            if (strpos($body, 'BEGIN:VCALENDAR') === false) {
                return ['success' => false, 'message' => 'Invalid ICS format'];
            }

            return [
                'success' => true,
                'message' => 'Successfully connected to ICS feed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
