<?php

declare(strict_types=1);

namespace Bookando\Core\Providers;

use Bookando\Core\Container\Container;
use Bookando\Core\Contracts\CustomerRepositoryInterface;
use Bookando\Core\Contracts\TenantManagerInterface;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Service\ActivityLogger;
use Bookando\Core\Service\DebugLogger;
use Bookando\Core\Service\UserSyncService;

/**
 * Central service provider for dependency registration.
 *
 * Registers all application services, repositories, and utilities
 * into the DI container, enabling dependency injection and testability.
 */
class ServiceProvider
{
    /**
     * Registers all services into the container.
     *
     * @return void
     */
    public static function register(): void
    {
        $container = Container::getInstance();

        // Register WordPress globals
        self::registerGlobals($container);

        // Register core services
        self::registerCoreServices($container);

        // Register module services (will be expanded)
        self::registerModuleServices($container);
    }

    /**
     * Registers WordPress global dependencies.
     *
     * @param Container $container
     * @return void
     */
    private static function registerGlobals(Container $container): void
    {
        // WordPress database
        $container->singleton('wpdb', function() {
            global $wpdb;
            return $wpdb;
        });
    }

    /**
     * Registers core framework services.
     *
     * @param Container $container
     * @return void
     */
    private static function registerCoreServices(Container $container): void
    {
        // Tenant Manager
        $container->singleton(
            TenantManagerInterface::class,
            fn() => TenantManager::class // Existing static class, wrap if needed
        );

        // Activity Logger
        $container->singleton(
            ActivityLogger::class,
            fn() => new ActivityLogger()
        );

        // Debug Logger
        $container->singleton(
            DebugLogger::class,
            fn() => new DebugLogger()
        );

        // User Sync Service
        $container->singleton(
            UserSyncService::class,
            fn() => new UserSyncService()
        );
    }

    /**
     * Registers module-specific services.
     *
     * This will be expanded as we refactor each module.
     *
     * @param Container $container
     * @return void
     */
    private static function registerModuleServices(Container $container): void
    {
        // Customers Module
        $container->singleton(
            \Bookando\Modules\customers\CustomerRepository::class,
            fn($c) => new \Bookando\Modules\customers\CustomerRepository()
        );

        $container->singleton(
            \Bookando\Modules\customers\CustomerValidator::class,
            fn($c) => new \Bookando\Modules\customers\CustomerValidator()
        );

        $container->singleton(
            \Bookando\Modules\customers\CustomerService::class,
            fn($c) => new \Bookando\Modules\customers\CustomerService(
                $c->get(\Bookando\Modules\customers\CustomerRepository::class),
                $c->get(\Bookando\Modules\customers\CustomerValidator::class)
            )
        );

        // Settings module (will be registered after refactoring)
        // Employees module (will be registered after refactoring)
        // Appointments module (will be registered after refactoring)
    }
}
