<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Modules\Offers\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'offers'; }
    protected static function getBaseRoute(): string     { return '/offers'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        // List all offers
        static::registerRoute('', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'list'],
            'permission_callback' => static::permissionWithFeature(),
        ]);

        // Create offer
        static::registerRoute('', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'create'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        // Get single offer
        static::registerRoute('(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'get'],
            'permission_callback' => static::permissionWithFeature(),
            'args'                => [
                'id' => [
                    'validate_callback' => static fn($value) => is_numeric($value) && (int) $value > 0,
                ],
            ],
        ]);

        // Update offer
        static::registerRoute('(?P<id>\d+)', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [RestHandler::class, 'update'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        // Delete offer
        static::registerRoute('(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [RestHandler::class, 'delete'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        // Bulk operations
        static::registerRoute('bulk', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'bulk'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        // Offer Types
        static::registerRoute('types', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getOfferTypes'],
            'permission_callback' => static::permissionWithFeature(),
        ]);

        // Get offers by type
        static::registerRoute('by-type/(?P<type>[a-z]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getByType'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // Calendar Views
        static::registerRoute('calendar/month', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getCalendarMonth'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        static::registerRoute('calendar/week', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getCalendarWeek'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        static::registerRoute('calendar/date', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getCalendarDate'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        static::registerRoute('calendar/range', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getDateRange'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // Upcoming courses
        static::registerRoute('upcoming', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getUpcoming'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // Search courses
        static::registerRoute('search', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'searchCourses'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // Check availability
        static::registerRoute('offers/(?P<id>\d+)/availability', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'checkAvailability'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);
    }

    private static function permissionWithFeature(?string $feature = null): callable
    {
        $basePermission = static::defaultPermission();

        return static function (WP_REST_Request $request) use ($basePermission, $feature) {
            $result = $basePermission($request);
            if ($result instanceof WP_Error || $result === false) {
                return $result;
            }

            if ($feature === null) {
                return true;
            }

            $featureResult = LicenseManager::ensureFeature(static::getModuleSlug(), $feature);
            if ($featureResult instanceof WP_Error) {
                return $featureResult;
            }

            return true;
        };
    }
}
