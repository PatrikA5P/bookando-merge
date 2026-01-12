<?php
namespace Bookando\Core\Api;

use WP_Error;
use WP_REST_Response;

final class Response
{
    /**
     * Returns a generic success response with the provided payload.
     */
    public static function ok($data = null, array $meta = [], int $status = 200): WP_REST_Response
    {
        return self::respondSuccess($data, $meta, $status);
    }

    /**
     * Returns a response for newly created resources (HTTP 201).
     */
    public static function created($data = null, array $meta = []): WP_REST_Response
    {
        return self::respondSuccess($data, $meta, 201);
    }

    /**
     * Returns an update acknowledgement together with additional payload.
     */
    public static function updated(array $extra = [], array $meta = []): WP_REST_Response
    {
        return self::respondSuccess(['updated' => true] + $extra, $meta, 200);
    }

    /**
     * Returns a deletion acknowledgement including the hard/soft flag.
     */
    public static function deleted(bool $hard, array $extra = [], array $meta = []): WP_REST_Response
    {
        return self::respondSuccess(['deleted' => true, 'hard' => $hard] + $extra, $meta, 200);
    }

    /**
     * Returns a 204 no-content response without a JSON body.
     */
    public static function noContent(array $meta = []): WP_REST_Response
    {
        unset($meta); // 204 responses must not return a body payload.

        $response = new WP_REST_Response(null, 204);

        return $response;
    }

    /**
     * Builds an error response with the given payload/message and status code.
     */
    public static function error($error, int $status = 400, array $meta = []): WP_REST_Response
    {
        $code    = 'error';
        $message = 'Unknown error';
        $details = null;

        if ($error instanceof WP_Error) {
            $code    = (string) $error->get_error_code();
            $message = $error->get_error_message();
            $data    = $error->get_error_data();

            if (is_array($data) && isset($data['status'])) {
                $status = (int) $data['status'];
            }

            if ($data !== null) {
                $details = $data;
            }
        } elseif (is_array($error)) {
            $code    = (string) ($error['code'] ?? $code);
            $message = (string) ($error['message'] ?? $message);

            if (array_key_exists('details', $error)) {
                $details = $error['details'];
            } elseif (array_key_exists('data', $error)) {
                $details = $error['data'];
            }

            if (isset($error['status'])) {
                $status = (int) $error['status'];
            }

            if (isset($error['meta']) && is_array($error['meta'])) {
                $meta = array_merge($meta, $error['meta']);
            }
        } else {
            $message = (string) $error;
        }

        $payload = [
            'data'  => null,
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
            'meta'  => ['success' => false, 'status' => $status] + $meta,
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        return self::respond($payload, $status);
    }

    private static function respondSuccess($data, array $meta, int $status): WP_REST_Response
    {
        $payload = [
            'data' => $data,
            'meta' => ['success' => true] + $meta,
        ];

        return self::respond($payload, $status);
    }

    private static function respond(array $payload, int $status): WP_REST_Response
    {
        if (!array_key_exists('meta', $payload)) {
            $payload['meta'] = [];
        }

        $response = rest_ensure_response($payload);
        $response->set_status($status);

        return $response;
    }
}
