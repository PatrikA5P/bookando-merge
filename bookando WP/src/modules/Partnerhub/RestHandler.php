<?php

declare(strict_types=1);

namespace Bookando\Modules\Partnerhub;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Api\Response;
use Bookando\Core\Tenant\TenantManager;
use function __;

class RestHandler
{
    public static function partners($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'message' => __('Partner-API noch nicht implementiert', 'bookando'),
        ]);
    }

    public static function mappings($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'message' => __('Mappings-API noch nicht implementiert', 'bookando'),
        ]);
    }

    public static function rules($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'message' => __('Regeln-API noch nicht implementiert', 'bookando'),
        ]);
    }

    public static function feeds($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'message' => __('Feeds-API noch nicht implementiert', 'bookando'),
        ]);
    }

    public static function consents($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'message' => __('Consent-API noch nicht implementiert', 'bookando'),
        ]);
    }

    public static function dashboard($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'total_partners' => 0,
            'total_mappings' => 0,
            'total_consents' => 0,
            'pending_approvals' => 0,
        ]);
    }

    public static function audit_logs($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'message' => __('Audit-Logs-API noch nicht implementiert', 'bookando'),
        ]);
    }
}
