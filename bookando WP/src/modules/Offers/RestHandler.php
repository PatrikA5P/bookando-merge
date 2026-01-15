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
        $orderBy    = is_string($orderBy) ? Sanitizer::key($orderBy) : null;
        $order      = is_string($order) ? strtoupper($order) : 'DESC';

        $result = $model->getPage(
            (int) $pagination['page'],
            (int) $pagination['per_page'],
            $orderBy ?: null,
            $order
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
}
