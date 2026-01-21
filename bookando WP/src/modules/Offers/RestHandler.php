<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

use Bookando\Core\Api\Response;
use Bookando\Core\Util\Sanitizer;
use Bookando\Modules\Offers\Api\Api;
use Bookando\Modules\Offers\Model;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function __;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function is_wp_error;
use function strtolower;
use function strtoupper;

/**
 * REST API handler for offer/service operations.
 *
 * Manages the service catalog including service listings, creation,
 * updates, and deletion. Offers represent bookable services with
 * pricing, duration, and availability settings.
 */
final class RestHandler
{
    /**
     * Lists all offers with pagination and sorting.
     *
     * Retrieves a paginated list of offers/services with configurable
     * sorting by any field (e.g., name, price, created_at).
     *
     * Query parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 20)
     * - order_by: Field to sort by
     * - order: Sort direction (ASC/DESC)
     *
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response Response with offers array and pagination metadata
     */
    public static function list(WP_REST_Request $request): WP_REST_Response
    {
        $model      = new Model();
        $pagination = Api::getPaginationParams($request);
        $orderBy    = $request->get_param('order_by');
        $order      = $request->get_param('order');
        $offerType  = $request->get_param('offer_type');
        $orderBy    = is_string($orderBy) ? Sanitizer::key($orderBy) : null;
        $order      = is_string($order) ? strtoupper($order) : 'DESC';
        $offerType  = is_string($offerType) && OfferType::isValid($offerType) ? $offerType : null;

        $result = $model->getPage(
            (int) $pagination['page'],
            (int) $pagination['per_page'],
            $orderBy ?: null,
            $order,
            $offerType
        );

        return Response::ok(
            $result['items'],
            [
                'page'     => $result['page'],
                'per_page' => $result['perPage'],
                'total'    => $result['total'],
            ]
        );
    }

    /**
     * Retrieves a single offer by ID.
     *
     * Returns complete offer details including pricing, duration,
     * description, and custom field configurations.
     *
     * @param WP_REST_Request $request The REST request object with offer ID in URL
     * @return WP_REST_Response Response with offer data or 404 error
     */
    public static function get(WP_REST_Request $request): WP_REST_Response
    {
        $id = self::resolveOfferId($request);
        if ($id <= 0) {
            return Response::error(new WP_Error(
                'invalid_id',
                __('Ungültige Angebots-ID.', 'bookando'),
                ['status' => 400]
            ));
        }

        $model = new Model();
        $row   = $model->find($id);

        if ($row === null) {
            return Response::error(new WP_Error(
                'not_found',
                __('Nicht gefunden.', 'bookando'),
                ['status' => 404]
            ));
        }

        return Response::ok($row);
    }

    /**
     * Creates a new offer/service.
     *
     * Validates and sanitizes the request payload before creating
     * a new service offering in the database.
     *
     * Required fields in payload:
     * - name: Service name
     * - price: Service price
     * - duration: Duration in minutes
     *
     * @param WP_REST_Request $request The REST request object with JSON payload
     * @return WP_REST_Response Response with new offer ID or validation errors
     */
    public static function create(WP_REST_Request $request): WP_REST_Response
    {
        $model   = new Model();
        $payload = self::validatePayload($request, false);

        if (is_wp_error($payload)) {
            return Response::error($payload);
        }

        try {
            $newId = $model->create($payload);
        } catch (\Throwable $exception) {
            return Response::error(new WP_Error(
                'offers_create_failed',
                __('Erstellen fehlgeschlagen.', 'bookando'),
                ['status' => 500]
            ));
        }

        return Response::created(['id' => $newId]);
    }

    public static function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = self::resolveOfferId($request);
        if ($id <= 0) {
            return Response::error(new WP_Error(
                'invalid_id',
                __('Ungültige Angebots-ID.', 'bookando'),
                ['status' => 400]
            ));
        }

        $model = new Model();
        if ($model->find($id) === null) {
            return Response::error(new WP_Error(
                'not_found',
                __('Nicht gefunden.', 'bookando'),
                ['status' => 404]
            ));
        }

        $payload = self::validatePayload($request, true);
        if (is_wp_error($payload)) {
            return Response::error($payload);
        }

        try {
            $model->update($id, $payload);
        } catch (\Throwable $exception) {
            return Response::error(new WP_Error(
                'offers_update_failed',
                __('Aktualisierung fehlgeschlagen.', 'bookando'),
                ['status' => 500]
            ));
        }

        return Response::updated(['id' => $id]);
    }

    public static function delete(WP_REST_Request $request): WP_REST_Response
    {
        $id = self::resolveOfferId($request);
        if ($id <= 0) {
            return Response::error(new WP_Error(
                'invalid_id',
                __('Ungültige Angebots-ID.', 'bookando'),
                ['status' => 400]
            ));
        }

        $model = new Model();
        if ($model->find($id) === null) {
            return Response::error(new WP_Error(
                'not_found',
                __('Nicht gefunden.', 'bookando'),
                ['status' => 404]
            ));
        }

        $hard = self::toBool($request->get_param('hard'));

        try {
            $deleted = $model->delete($id, $hard);
        } catch (\Throwable $exception) {
            return Response::error(new WP_Error(
                'offers_delete_failed',
                __('Löschen fehlgeschlagen.', 'bookando'),
                ['status' => 500]
            ));
        }

        if (!$deleted) {
            return Response::error(new WP_Error(
                'not_found',
                __('Nicht gefunden.', 'bookando'),
                ['status' => 404]
            ));
        }

        return Response::deleted($hard, ['id' => $id]);
    }

    public static function bulk(WP_REST_Request $request): WP_REST_Response
    {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return Response::error(new WP_Error(
                'invalid_payload',
                __('Ungültige Sammelaktion.', 'bookando'),
                ['status' => 422]
            ));
        }

        $action = Sanitizer::key($body['action'] ?? '');
        $idsRaw = $body['ids'] ?? [];
        if ($action === '' || !is_array($idsRaw)) {
            return Response::error(new WP_Error(
                'invalid_payload',
                __('Ungültige Sammelaktion.', 'bookando'),
                ['status' => 422]
            ));
        }

        $ids = array_values(array_filter(array_map('intval', $idsRaw), static fn(int $value): bool => $value > 0));
        if ($ids === []) {
            return Response::error(new WP_Error(
                'invalid_payload',
                __('Es wurden keine gültigen IDs übermittelt.', 'bookando'),
                ['status' => 422]
            ));
        }

        $hard = $action === 'delete_hard';
        $soft = $action === 'delete_soft';
        if (!$hard && !$soft) {
            return Response::error(new WP_Error(
                'invalid_payload',
                __('Nicht unterstützte Sammelaktion.', 'bookando'),
                ['status' => 422]
            ));
        }

        $model   = new Model();
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $deleted += $model->delete($id, $hard) ? 1 : 0;
            } catch (\Throwable $exception) {
                return Response::error(new WP_Error(
                    'offers_bulk_failed',
                    __('Sammelaktion fehlgeschlagen.', 'bookando'),
                    ['status' => 500]
                ));
            }
        }

        return Response::ok([
            'hard'      => $hard,
            'requested' => count($ids),
            'deleted'   => $deleted,
        ]);
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    private static function validatePayload(WP_REST_Request $request, bool $partial)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            return new WP_Error(
                'invalid_payload',
                __('Ungültige Eingabedaten.', 'bookando'),
                ['status' => 422]
            );
        }

        $data = [];

        if (array_key_exists('title', $payload)) {
            $title = Sanitizer::text((string) $payload['title']);
            if ($title === '') {
                return new WP_Error(
                    'invalid_payload',
                    __('Titel ist erforderlich.', 'bookando'),
                    ['status' => 422]
                );
            }
            $data['title'] = $title;
        } elseif (!$partial) {
            return new WP_Error(
                'invalid_payload',
                __('Titel ist erforderlich.', 'bookando'),
                ['status' => 422]
            );
        }

        if (array_key_exists('status', $payload)) {
            $status = Sanitizer::key((string) $payload['status']);
            if ($status === '') {
                return new WP_Error(
                    'invalid_payload',
                    __('Status ist ungültig.', 'bookando'),
                    ['status' => 422]
                );
            }
            $data['status'] = $status;
        }

        return $data;
    }

    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower($value);

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private static function resolveOfferId(WP_REST_Request $request): int
    {
        $raw = $request->get_param('id');
        if ($raw === null) {
            $raw = $request->get_param('subkey');
        }

        return is_numeric($raw) ? (int) $raw : 0;
    }

    /**
     * Get offers by type
     */
    public static function getByType(WP_REST_Request $request): WP_REST_Response
    {
        $offerType = $request->get_param('type');

        if (!is_string($offerType) || !OfferType::isValid($offerType)) {
            return Response::error(new WP_Error(
                'invalid_offer_type',
                __('Ungültiger Angebotstyp.', 'bookando'),
                ['status' => 400]
            ));
        }

        $model = new Model();
        $offers = $model->getByType($offerType);

        return Response::ok([
            'offers' => $offers,
            'type' => $offerType,
            'type_label' => OfferType::getLabel($offerType),
        ]);
    }

    /**
     * Get calendar month view
     */
    public static function getCalendarMonth(WP_REST_Request $request): WP_REST_Response
    {
        $year = $request->get_param('year');
        $month = $request->get_param('month');

        if (!is_numeric($year) || !is_numeric($month)) {
            return Response::error(new WP_Error(
                'invalid_params',
                __('Jahr und Monat sind erforderlich.', 'bookando'),
                ['status' => 400]
            ));
        }

        $year = (int)$year;
        $month = (int)$month;

        if ($month < 1 || $month > 12) {
            return Response::error(new WP_Error(
                'invalid_month',
                __('Monat muss zwischen 1 und 12 liegen.', 'bookando'),
                ['status' => 400]
            ));
        }

        $data = CalendarViewController::getMonthView($year, $month);

        return Response::ok($data);
    }

    /**
     * Get calendar week view
     */
    public static function getCalendarWeek(WP_REST_Request $request): WP_REST_Response
    {
        $startDate = $request->get_param('start_date');

        if (!is_string($startDate) || !strtotime($startDate)) {
            return Response::error(new WP_Error(
                'invalid_date',
                __('Ungültiges Startdatum.', 'bookando'),
                ['status' => 400]
            ));
        }

        $data = CalendarViewController::getWeekView($startDate);

        return Response::ok($data);
    }

    /**
     * Get courses for specific date
     */
    public static function getCalendarDate(WP_REST_Request $request): WP_REST_Response
    {
        $date = $request->get_param('date');

        if (!is_string($date) || !strtotime($date)) {
            return Response::error(new WP_Error(
                'invalid_date',
                __('Ungültiges Datum.', 'bookando'),
                ['status' => 400]
            ));
        }

        $courses = CalendarViewController::getDateView($date);

        return Response::ok([
            'date' => $date,
            'courses' => $courses,
            'count' => count($courses),
        ]);
    }

    /**
     * Get upcoming courses
     */
    public static function getUpcoming(WP_REST_Request $request): WP_REST_Response
    {
        $limit = $request->get_param('limit');
        $limit = is_numeric($limit) ? (int)$limit : 20;

        $courses = CalendarViewController::getUpcomingList($limit);

        return Response::ok([
            'courses' => $courses,
            'count' => count($courses),
        ]);
    }

    /**
     * Get date range view with grouping
     */
    public static function getDateRange(WP_REST_Request $request): WP_REST_Response
    {
        $startDate = $request->get_param('start_date');
        $endDate = $request->get_param('end_date');
        $groupBy = $request->get_param('group_by');

        if (!is_string($startDate) || !strtotime($startDate)) {
            return Response::error(new WP_Error(
                'invalid_start_date',
                __('Ungültiges Startdatum.', 'bookando'),
                ['status' => 400]
            ));
        }

        if (!is_string($endDate) || !strtotime($endDate)) {
            return Response::error(new WP_Error(
                'invalid_end_date',
                __('Ungültiges Enddatum.', 'bookando'),
                ['status' => 400]
            ));
        }

        $groupBy = is_string($groupBy) ? $groupBy : 'date';
        if (!in_array($groupBy, ['date', 'week', 'month', 'none'], true)) {
            $groupBy = 'date';
        }

        $data = CalendarViewController::getRangeView($startDate, $endDate, $groupBy);

        return Response::ok($data);
    }

    /**
     * Search courses with filters
     */
    public static function searchCourses(WP_REST_Request $request): WP_REST_Response
    {
        $criteria = $request->get_json_params() ?: [];

        $courses = CalendarViewController::searchCourses($criteria);

        return Response::ok([
            'courses' => $courses,
            'count' => count($courses),
            'criteria' => $criteria,
        ]);
    }

    /**
     * Check if offer has available spots
     */
    public static function checkAvailability(WP_REST_Request $request): WP_REST_Response
    {
        $id = self::resolveOfferId($request);
        if ($id <= 0) {
            return Response::error(new WP_Error(
                'invalid_id',
                __('Ungültige Angebots-ID.', 'bookando'),
                ['status' => 400]
            ));
        }

        $model = new Model();
        $offer = $model->find($id);

        if (!$offer) {
            return Response::error(new WP_Error(
                'not_found',
                __('Angebot nicht gefunden.', 'bookando'),
                ['status' => 404]
            ));
        }

        $hasSpots = $model->hasAvailableSpots($id);

        return Response::ok([
            'id' => $id,
            'has_available_spots' => $hasSpots,
            'max_participants' => $offer['max_participants'],
            'current_participants' => $offer['current_participants'],
            'remaining_spots' => $offer['max_participants'] !== null
                ? max(0, (int)$offer['max_participants'] - (int)$offer['current_participants'])
                : null,
        ]);
    }

    /**
     * Get offer types metadata
     */
    public static function getOfferTypes(WP_REST_Request $request): WP_REST_Response
    {
        $types = [];

        foreach (OfferType::getAll() as $type) {
            $types[] = [
                'value' => $type,
                'label' => OfferType::getLabel($type),
                'description' => OfferType::getDescription($type),
            ];
        }

        return Response::ok(['types' => $types]);
    }
}
