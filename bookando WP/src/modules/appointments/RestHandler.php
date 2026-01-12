<?php

declare(strict_types=1);

namespace Bookando\Modules\appointments;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Api\Response;
use Bookando\Core\Dispatcher\RestModuleGuard;
use WP_REST_Server;
use Bookando\Core\Util\Sanitizer;
use function __;
use function wp_date;
use function wp_timezone;
use function get_option;
use function time;

/**
 * REST API handler for appointment and event operations.
 *
 * Manages appointment scheduling, timeline views, event management,
 * and calendar operations. Handles timezone conversions and recurring
 * appointment patterns.
 */
class RestHandler
{
    /**
     * Retrieves timeline view of appointments and events.
     *
     * Returns appointments and events grouped by date within a specified
     * date range. Results are timezone-aware and formatted for calendar display.
     *
     * Query parameters:
     * - from: Start date (ISO 8601 or Y-m-d format)
     * - to: End date (ISO 8601 or Y-m-d format)
     *
     * @param array $params URL parameters
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response Response with timeline data grouped by date
     */
    public static function timeline($params, WP_REST_Request $request): WP_REST_Response
    {
        [$fromUtc, $toUtc, $rangeLocal] = self::resolveRange($request);
        $tz = wp_timezone();

        $appointmentsModel = new Model();
        $eventModel = new EventModel();

        $appointments = $appointmentsModel->timeline($fromUtc, $toUtc);
        $events = $eventModel->timeline($fromUtc, $toUtc);

        $groups = [];
        $dateFormat = get_option('date_format') ?: 'Y-m-d';

        foreach ($appointments as $row) {
            [$dateKey, $item] = self::formatAppointmentItem($row, $tz);
            $groups[$dateKey]['date'] = $dateKey;
            $groups[$dateKey]['label'] = $groups[$dateKey]['label'] ?? wp_date($dateFormat, $item['start_local_timestamp'], $tz);
            $groups[$dateKey]['items'][] = $item;
        }

        foreach ($events as $row) {
            [$dateKey, $item] = self::formatEventItem($row, $tz);
            $groups[$dateKey]['date'] = $dateKey;
            $groups[$dateKey]['label'] = $groups[$dateKey]['label'] ?? wp_date($dateFormat, $item['start_local_timestamp'], $tz);
            $groups[$dateKey]['items'][] = $item;
        }

        foreach ($groups as &$group) {
            if (!empty($group['items'])) {
                usort($group['items'], function ($a, $b) {
                    return $a['start_local_timestamp'] <=> $b['start_local_timestamp'];
                });
            }
            foreach ($group['items'] as &$item) {
                unset($item['start_local_timestamp'], $item['end_local_timestamp']);
            }
        }
        unset($group, $item);

        ksort($groups);
        $data = array_values(array_map(function ($group) {
            return [
                'date'  => $group['date'],
                'label' => $group['label'],
                'items' => array_values($group['items']),
            ];
        }, $groups));

        return Response::ok($data, [
            'from' => $rangeLocal['from'],
            'to'   => $rangeLocal['to'],
        ]);
    }

    /**
     * Handles appointment CRUD operations.
     *
     * Manages individual appointment operations including creation, retrieval,
     * updates, and deletion. Handles timezone conversion for start/end times
     * and validates appointment data against business rules.
     *
     * Supported operations:
     * - GET /appointments/{id} - Get single appointment
     * - GET /appointments - List appointments
     * - POST /appointments - Create appointment
     * - PUT /appointments/{id} - Update appointment
     * - DELETE /appointments/{id} - Delete appointment
     *
     * @param array $params URL parameters including optional appointment ID
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response Response with appointment data or confirmation
     */
    public static function appointments($params, WP_REST_Request $request): WP_REST_Response
    {
        $method = strtoupper($request->get_method());
        $id = isset($params['subkey']) ? (int) $params['subkey'] : 0;

        // UPDATE (PUT)
        if ($method === 'PUT' && $id > 0) {
            $payload = (array) $request->get_json_params();
            $siteTz = wp_timezone();

            $data = [];

            if (isset($payload['customer_id'])) {
                $data['customer_id'] = (int) $payload['customer_id'];
            }
            if (isset($payload['employee_id'])) {
                $data['employee_id'] = $payload['employee_id'] ? (int) $payload['employee_id'] : null;
            }
            if (isset($payload['service_id'])) {
                $data['service_id'] = $payload['service_id'] ? (int) $payload['service_id'] : null;
            }
            if (isset($payload['location_id'])) {
                $data['location_id'] = $payload['location_id'] ? (int) $payload['location_id'] : null;
            }
            if (isset($payload['event_id'])) {
                $data['event_id'] = $payload['event_id'] ? (int) $payload['event_id'] : null;
            }

            if (isset($payload['starts_at']) || isset($payload['start'])) {
                $startInputRaw = $payload['starts_at'] ?? ($payload['start'] ?? '');
                $startsAt = Sanitizer::text(is_scalar($startInputRaw) ? (string) $startInputRaw : '');
                $startDt = self::parseDateTime($startsAt, $siteTz);
                if ($startDt) {
                    $data['starts_at_utc'] = $startDt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $data['client_tz'] = $startDt->getTimezone()->getName();
                }
            }

            if (isset($payload['ends_at']) || isset($payload['end'])) {
                $endInputRaw = $payload['ends_at'] ?? ($payload['end'] ?? '');
                $endsAt = Sanitizer::text(is_scalar($endInputRaw) ? (string) $endInputRaw : '');
                $endDt = self::parseDateTime($endsAt, $siteTz);
                if ($endDt) {
                    $data['ends_at_utc'] = $endDt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                }
            }

            if (isset($payload['status'])) {
                $data['status'] = Sanitizer::key($payload['status'] ?? 'confirmed') ?: 'confirmed';
            }
            if (isset($payload['price'])) {
                $data['price'] = (float) $payload['price'];
            }
            if (isset($payload['persons'])) {
                $data['persons'] = (int) $payload['persons'];
            }
            if (isset($payload['meta']) && is_array($payload['meta'])) {
                $data['meta'] = $payload['meta'];
            }

            $model = new Model();
            $updated = $model->updateAppointment($id, $data);

            if (!$updated) {
                return Response::error([
                    'code'    => 'update_failed',
                    'message' => __('Termin konnte nicht aktualisiert werden.', 'bookando'),
                ], 400);
            }

            return Response::ok(['updated' => true, 'id' => $id]);
        }

        // CREATE (POST)
        if ($method !== 'POST') {
            return Response::error([
                'code'    => 'method_not_allowed',
                'message' => __('Methode nicht unterstützt.', 'bookando'),
            ], 405);
        }

        $payload = (array) $request->get_json_params();
        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : 0;
        $serviceId = isset($payload['service_id']) ? (int) $payload['service_id'] : 0;
        $startInputRaw = $payload['starts_at'] ?? ($payload['start'] ?? '');
        $endInputRaw = $payload['ends_at'] ?? ($payload['end'] ?? '');
        $startsAt  = Sanitizer::text(is_scalar($startInputRaw) ? (string) $startInputRaw : '');
        $endsAt    = Sanitizer::text(is_scalar($endInputRaw) ? (string) $endInputRaw : '');

        if (!$customerId || !$serviceId || !$startsAt) {
            return Response::error([
                'code'    => 'invalid_params',
                'message' => __('Die Felder customer_id, service_id und starts_at müssen angegeben werden.', 'bookando'),
            ], 400);
        }

        $siteTz = wp_timezone();
        $startDt = self::parseDateTime($startsAt, $siteTz);
        $endDt = $endsAt ? self::parseDateTime($endsAt, $siteTz) : null;

        if (!$startDt) {
            return Response::error([
                'code'    => 'invalid_start',
                'message' => __('Startzeitpunkt konnte nicht verarbeitet werden.', 'bookando'),
            ], 400);
        }
        if (!$endDt) {
            $duration = DateInterval::createFromDateString('1 hour');
            $endDt = $duration instanceof DateInterval ? $startDt->add($duration) : $startDt;
        }

        $status = Sanitizer::key($payload['status'] ?? 'confirmed') ?: 'confirmed';
        $allowedStatus = ['pending','approved','confirmed','cancelled','noshow'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'confirmed';
        }

        $meta = [];
        if (!empty($payload['meta']) && is_array($payload['meta'])) {
            $meta = $payload['meta'];
        }

        $model = new Model();
        $id = $model->createAppointment([
            'customer_id'   => $customerId,
            'service_id'    => $serviceId,
            'employee_id'   => isset($payload['employee_id']) ? (int) $payload['employee_id'] : null,
            'location_id'   => isset($payload['location_id']) ? (int) $payload['location_id'] : null,
            'event_id'      => isset($payload['event_id']) ? (int) $payload['event_id'] : null,
            'status'        => $status,
            'starts_at_utc' => $startDt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'ends_at_utc'   => $endDt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'client_tz'     => $startDt->getTimezone()->getName(),
            'price'         => isset($payload['price']) ? (float) $payload['price'] : null,
            'persons'       => isset($payload['persons']) ? (int) $payload['persons'] : 1,
            'meta'          => $meta,
        ]);

        return Response::created(['id' => $id]);
    }

    public static function assign($params, WP_REST_Request $request): WP_REST_Response
    {
        if (strtoupper($request->get_method()) !== 'POST') {
            return Response::error([
                'code'    => 'method_not_allowed',
                'message' => __('Methode nicht unterstützt.', 'bookando'),
            ], 405);
        }

        $payload = (array) $request->get_json_params();
        $eventId = isset($payload['event_id']) ? (int) $payload['event_id'] : 0;
        $periodId = isset($payload['period_id']) ? (int) $payload['period_id'] : 0;
        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : 0;

        if (!$eventId || !$customerId) {
            return Response::error([
                'code'    => 'invalid_params',
                'message' => __('Die Felder event_id und customer_id müssen angegeben werden.', 'bookando'),
            ], 400);
        }

        $eventModel = new EventModel();
        $period = $periodId ? $eventModel->findPeriod($periodId) : null;

        $siteTz = wp_timezone();
        $startInput = Sanitizer::text(is_scalar($payload['starts_at'] ?? null) ? (string) $payload['starts_at'] : '');
        $endInput = Sanitizer::text(is_scalar($payload['ends_at'] ?? null) ? (string) $payload['ends_at'] : '');

        if ($period) {
            $startDt = self::parseDateTime($period['period_start_utc'] . ' UTC');
            $endDt = self::parseDateTime($period['period_end_utc'] . ' UTC');
        } else {
            $startDt = $startInput ? self::parseDateTime($startInput, $siteTz) : null;
            $endDt = $endInput ? self::parseDateTime($endInput, $siteTz) : null;
        }

        if (!$startDt) {
            return Response::error([
                'code'    => 'invalid_start',
                'message' => __('Startzeitpunkt der Veranstaltung konnte nicht ermittelt werden.', 'bookando'),
            ], 400);
        }
        if (!$endDt) {
            $duration = DateInterval::createFromDateString('1 hour');
            $endDt = $duration instanceof DateInterval ? $startDt->add($duration) : $startDt;
        }

        $meta = [];
        if (!empty($payload['meta']) && is_array($payload['meta'])) {
            $meta = $payload['meta'];
        }
        if ($period) {
            $meta['event_period_id'] = $period['period_id'];
        }

        $model = new Model();
        $id = $model->createAppointment([
            'customer_id'   => $customerId,
            'service_id'    => isset($payload['service_id']) ? (int) $payload['service_id'] : null,
            'employee_id'   => isset($payload['employee_id']) ? (int) $payload['employee_id'] : null,
            'location_id'   => isset($payload['location_id']) ? (int) $payload['location_id'] : null,
            'event_id'      => $eventId,
            'status'        => Sanitizer::key($payload['status'] ?? 'confirmed') ?: 'confirmed',
            'starts_at_utc' => $startDt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'ends_at_utc'   => $endDt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'client_tz'     => $startDt->getTimezone()->getName(),
            'persons'       => isset($payload['persons']) ? (int) $payload['persons'] : 1,
            'meta'          => $meta,
        ]);

        return Response::created([
            'id' => $id,
            'event_id' => $eventId,
            'period_id' => $period ? (int) $period['period_id'] : null,
        ]);
    }

    public static function lookups($params, WP_REST_Request $request): WP_REST_Response
    {
        $search = Sanitizer::text(is_scalar($request->get_param('search')) ? (string) $request->get_param('search') : '');
        $limit  = (int) ($request->get_param('limit') ?? 25);

        $model = new Model();
        $eventModel = new EventModel();
        $tz = wp_timezone();

        $customers = array_map(function ($row) {
            return [
                'id'    => (int) $row['id'],
                'name'  => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ($row['email'] ?? ''),
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
            ];
        }, $model->getCustomerOptions($search, $limit));

        $services = array_map(function ($row) {
            return [
                'id'     => (int) $row['id'],
                'name'   => $row['name'] ?? $row['title'] ?? '',
                'status' => $row['status'] ?? '',
            ];
        }, $model->getServiceOptions($search, $limit));

        $eventsRaw = $eventModel->getEventOptions($search, $limit);
        $events = [];
        foreach ($eventsRaw as $row) {
            $startLocal = self::toLocalDate($row['period_start_utc'] ?? null, $tz);
            $endLocal = self::toLocalDate($row['period_end_utc'] ?? null, $tz);
            $events[] = [
                'event_id'   => (int) $row['event_id'],
                'period_id'  => isset($row['period_id']) ? (int) $row['period_id'] : null,
                'name'       => $row['event_name'],
                'type'       => $row['event_type'],
                'status'     => $row['event_status'],
                'start_local'=> $startLocal ? $startLocal->format(DateTimeInterface::ATOM) : null,
                'end_local'  => $endLocal ? $endLocal->format(DateTimeInterface::ATOM) : null,
            ];
        }

        return Response::ok([
            'customers' => $customers,
            'services'  => $services,
            'events'    => $events,
        ]);
    }

    private static function resolveRange(WP_REST_Request $request): array
    {
        $siteTz = wp_timezone();
        $fromInput = Sanitizer::date(is_scalar($request->get_param('from')) ? (string) $request->get_param('from') : null);
        $toInput = Sanitizer::date(is_scalar($request->get_param('to')) ? (string) $request->get_param('to') : null);

        $from = self::parseDateTime($fromInput ?: 'now', $siteTz);
        if (!$from) {
            $from = self::nowInTimezone($siteTz);
        }
        $from = $from->setTime(0, 0);

        $to = $toInput ? self::parseDateTime($toInput, $siteTz) : null;
        if (!$to) {
            $interval = DateInterval::createFromDateString('14 days');
            $to = $interval instanceof DateInterval ? $from->add($interval) : $from;
        }
        $to = $to->setTime(23, 59, 59);

        return [
            $from->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            $to->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            [
                'from' => $from->format(DateTimeInterface::ATOM),
                'to'   => $to->format(DateTimeInterface::ATOM),
            ],
        ];
    }

    private static function parseDateTime(string $value, ?DateTimeZone $defaultTz = null): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if ($defaultTz) {
                return new DateTimeImmutable($value, $defaultTz);
            }
            return new DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function formatAppointmentItem(array $row, DateTimeZone $tz): array
    {
        $start = self::toLocalDate($row['starts_at_utc'] ?? null, $tz);
        $end = self::toLocalDate($row['ends_at_utc'] ?? null, $tz);

        $dateKey = $start ? $start->format('Y-m-d') : date('Y-m-d');
        $meta = [];
        if (!empty($row['meta'])) {
            $decoded = json_decode((string) $row['meta'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $item = [
            'type' => 'appointment',
            'id'   => (int) $row['id'],
            'status' => $row['status'] ?? 'pending',
            'start_utc' => $row['starts_at_utc'],
            'end_utc'   => $row['ends_at_utc'],
            'start_local' => $start ? $start->format(DateTimeInterface::ATOM) : null,
            'end_local'   => $end ? $end->format(DateTimeInterface::ATOM) : null,
            'start_local_timestamp' => $start ? $start->getTimestamp() : 0,
            'end_local_timestamp'   => $end ? $end->getTimestamp() : 0,
            'service' => [
                'id'   => isset($row['service_id']) ? (int) $row['service_id'] : null,
                'name' => $row['service_title'] ?? null,
            ],
            'customer' => [
                'id'    => isset($row['customer_id']) ? (int) $row['customer_id'] : null,
                'name'  => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ($row['email'] ?? ''),
                'email' => $row['email'] ?? null,
            ],
            'event' => [
                'id'   => isset($row['event_id']) ? (int) $row['event_id'] : null,
                'name' => $row['event_name'] ?? null,
                'type' => $row['event_type'] ?? null,
            ],
            'persons' => isset($row['persons']) ? (int) $row['persons'] : 1,
            'meta' => $meta,
        ];

        return [$dateKey, $item];
    }

    private static function formatEventItem(array $row, DateTimeZone $tz): array
    {
        $start = self::toLocalDate($row['period_start_utc'] ?? null, $tz);
        $end = self::toLocalDate($row['period_end_utc'] ?? null, $tz);

        $dateKey = $start ? $start->format('Y-m-d') : date('Y-m-d');
        $item = [
            'type' => 'event',
            'event_id'  => (int) $row['event_id'],
            'period_id' => isset($row['period_id']) ? (int) $row['period_id'] : null,
            'name'      => $row['event_name'] ?? '',
            'event_type'=> $row['event_type'] ?? '',
            'status'    => $row['event_status'] ?? '',
            'start_utc' => $row['period_start_utc'],
            'end_utc'   => $row['period_end_utc'],
            'start_local' => $start ? $start->format(DateTimeInterface::ATOM) : null,
            'end_local'   => $end ? $end->format(DateTimeInterface::ATOM) : null,
            'start_local_timestamp' => $start ? $start->getTimestamp() : 0,
            'end_local_timestamp'   => $end ? $end->getTimestamp() : 0,
            'participants' => isset($row['participant_count']) ? (int) $row['participant_count'] : 0,
            'capacity'     => isset($row['max_capacity']) ? (int) $row['max_capacity'] : null,
            'price'        => isset($row['price']) ? (float) $row['price'] : null,
        ];

        return [$dateKey, $item];
    }

    private static function toLocalDate(?string $utc, DateTimeZone $tz): ?DateTimeImmutable
    {
        if (!$utc) {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
            return $dt->setTimezone($tz);
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function nowInTimezone(DateTimeZone $tz): DateTimeImmutable
    {
        $timestamp = (string) time();
        $now = DateTimeImmutable::createFromFormat('U', $timestamp, $tz);
        if ($now instanceof DateTimeImmutable) {
            return $now;
        }

        $utc = DateTimeImmutable::createFromFormat('U', $timestamp, new DateTimeZone('UTC'));
        if ($utc instanceof DateTimeImmutable) {
            return $utc->setTimezone($tz);
        }

        return new DateTimeImmutable();
    }

}
