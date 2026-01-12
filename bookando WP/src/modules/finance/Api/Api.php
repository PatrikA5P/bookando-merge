<?php

declare(strict_types=1);

namespace Bookando\Modules\finance\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\finance\RestHandler;
use Bookando\Modules\finance\PaymentRestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'finance'; }
    protected static function getBaseRoute(): string     { return '/finance'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        static::registerRoute('state', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [RestHandler::class, 'getState'],
        ]);

        static::registerRoute('invoices', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [RestHandler::class, 'saveInvoice'],
        ]);

        static::registerRoute('invoices/(?P<id>[a-zA-Z0-9-]+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => [RestHandler::class, 'deleteInvoice'],
        ]);

        static::registerRoute('credit_notes', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [RestHandler::class, 'saveCreditNote'],
        ]);

        static::registerRoute('credit_notes/(?P<id>[a-zA-Z0-9-]+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => [RestHandler::class, 'deleteCreditNote'],
        ]);

        static::registerRoute('discount_codes', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [RestHandler::class, 'saveDiscountCode'],
        ]);

        static::registerRoute('discount_codes/(?P<id>[a-zA-Z0-9-]+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => [RestHandler::class, 'deleteDiscountCode'],
        ]);

        static::registerRoute('settings', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [RestHandler::class, 'saveSettings'],
        ]);

        static::registerRoute('export', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [RestHandler::class, 'exportLedger'],
        ]);

        // Payment Gateway Routes
        static::registerRoute('payment/gateways', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [PaymentRestHandler::class, 'getGateways'],
        ]);

        static::registerRoute('payment/gateways/(?P<gateway_id>[a-z_]+)/config', [
            'methods'  => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
            'callback' => [PaymentRestHandler::class, 'gatewayConfig'],
        ]);

        static::registerRoute('payment/gateways/(?P<gateway_id>[a-z_]+)/test', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [PaymentRestHandler::class, 'testGateway'],
        ]);

        static::registerRoute('payment/create', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [PaymentRestHandler::class, 'createPayment'],
        ]);

        static::registerRoute('payment/(?P<payment_id>[a-zA-Z0-9_-]+)/capture', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [PaymentRestHandler::class, 'capturePayment'],
        ]);

        static::registerRoute('payment/(?P<payment_id>[a-zA-Z0-9_-]+)/refund', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [PaymentRestHandler::class, 'refundPayment'],
        ]);

        static::registerRoute('payment/(?P<payment_id>[a-zA-Z0-9_-]+)/status', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [PaymentRestHandler::class, 'paymentStatus'],
        ]);
    }
}
