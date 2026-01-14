<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\academy\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'academy'; }
    protected static function getBaseRoute(): string     { return '/academy'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        static::registerRoute('state', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'getState'],
            'permission_callback' => [RestHandler::class, 'canManage'],
        ]);

        static::registerRoute('courses', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'saveCourse'],
            'permission_callback' => [RestHandler::class, 'canManage'],
        ]);

        static::registerRoute('courses/(?P<id>[a-zA-Z0-9-]+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [RestHandler::class, 'deleteCourse'],
            'permission_callback' => [RestHandler::class, 'canManage'],
            'args'                => [
                'id' => [
                    'type'     => 'string',
                    'required' => true,
                ],
            ],
        ]);

        static::registerRoute('training_cards', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'saveTrainingCard'],
            'permission_callback' => [RestHandler::class, 'canManage'],
        ]);

        static::registerRoute('training_cards/(?P<id>[a-zA-Z0-9-]+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [RestHandler::class, 'deleteTrainingCard'],
            'permission_callback' => [RestHandler::class, 'canManage'],
            'args'                => [
                'id' => [
                    'type'     => 'string',
                    'required' => true,
                ],
            ],
        ]);

        static::registerRoute('training_cards_progress', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'updateProgress'],
            'permission_callback' => [RestHandler::class, 'canManage'],
        ]);
    }
}
