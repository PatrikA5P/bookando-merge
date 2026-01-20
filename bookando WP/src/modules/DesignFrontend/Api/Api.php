<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\DesignFrontend\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'design-frontend'; }
    protected static function getBaseRoute(): string     { return '/frontend'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        // Public offers endpoint
        static::registerRoute('offers', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getOffers'],
            'permission_callback' => '__return_true', // Public
        ]);

        // Single offer endpoint
        static::registerRoute('offers/(?P<id>[0-9]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getOffer'],
            'permission_callback' => '__return_true', // Public
        ]);

        // Auth: Email login
        static::registerRoute('auth/email/login', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'authEmailLogin'],
            'permission_callback' => '__return_true',
        ]);

        // Auth: Email verification
        static::registerRoute('auth/email/verify', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'authEmailVerify'],
            'permission_callback' => '__return_true',
        ]);

        // Auth: Google login
        static::registerRoute('auth/google/login', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'authGoogleLogin'],
            'permission_callback' => '__return_true',
        ]);

        // Auth: Apple login
        static::registerRoute('auth/apple/login', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'authAppleLogin'],
            'permission_callback' => '__return_true',
        ]);

        // Auth: Logout
        static::registerRoute('auth/logout', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'authLogout'],
            'permission_callback' => [RestHandler::class, 'isAuthenticated'],
        ]);

        // Customer Portal: My bookings
        static::registerRoute('portal/customer/bookings', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getCustomerBookings'],
            'permission_callback' => [RestHandler::class, 'isAuthenticated'],
        ]);

        // Customer Portal: My invoices
        static::registerRoute('portal/customer/invoices', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getCustomerInvoices'],
            'permission_callback' => [RestHandler::class, 'isAuthenticated'],
        ]);

        // Customer Portal: My progress
        static::registerRoute('portal/customer/progress', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getCustomerProgress'],
            'permission_callback' => [RestHandler::class, 'isAuthenticated'],
        ]);

        // Employee Portal: My schedule
        static::registerRoute('portal/employee/schedule', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getEmployeeSchedule'],
            'permission_callback' => [RestHandler::class, 'isEmployee'],
        ]);

        // Employee Portal: My students
        static::registerRoute('portal/employee/students', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getEmployeeStudents'],
            'permission_callback' => [RestHandler::class, 'isEmployee'],
        ]);

        // Booking: Create booking
        static::registerRoute('booking', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'createBooking'],
            'permission_callback' => [RestHandler::class, 'isAuthenticated'],
        ]);
    }
}
