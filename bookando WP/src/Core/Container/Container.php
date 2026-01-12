<?php

declare(strict_types=1);

namespace Bookando\Core\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * PSR-11 compliant Dependency Injection Container.
 *
 * Provides service registration, resolution, and singleton management
 * for decoupling application components and enabling testability.
 *
 * Features:
 * - Singleton registration for shared instances
 * - Factory registration for new instances per call
 * - Auto-resolution with constructor injection
 * - Circular dependency detection
 *
 * @example
 * $container = Container::getInstance();
 * $container->singleton(CustomerService::class, fn($c) => new CustomerService($c->get('wpdb')));
 * $service = $container->get(CustomerService::class);
 */
final class Container implements ContainerInterface
{
    private static ?Container $instance = null;

    /** @var array<string, array{type: string, concrete: callable}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    private function __construct()
    {
        // Singleton pattern
    }

    /**
     * Gets the global container instance.
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registers a singleton service.
     *
     * The service will be instantiated once and reused for all subsequent requests.
     *
     * @param string $abstract Service identifier (typically the class name)
     * @param callable $concrete Factory function that creates the service
     * @return void
     */
    public function singleton(string $abstract, callable $concrete): void
    {
        $this->bindings[$abstract] = [
            'type' => 'singleton',
            'concrete' => $concrete,
        ];
    }

    /**
     * Registers a transient service.
     *
     * A new instance will be created for each request.
     *
     * @param string $abstract Service identifier
     * @param callable $concrete Factory function
     * @return void
     */
    public function bind(string $abstract, callable $concrete): void
    {
        $this->bindings[$abstract] = [
            'type' => 'bind',
            'concrete' => $concrete,
        ];
    }

    /**
     * Registers an existing instance as a singleton.
     *
     * @param string $abstract Service identifier
     * @param object $instance The instance to register
     * @return void
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = [
            'type' => 'singleton',
            'concrete' => fn() => $instance,
        ];
    }

    /**
     * Resolves and returns a service from the container.
     *
     * @param string $id Service identifier
     * @return mixed The resolved service
     * @throws NotFoundException If the service is not registered
     * @throws CircularDependencyException If circular dependency is detected
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service '{$id}' not found in container");
        }

        // Circular dependency detection
        if (isset($this->resolving[$id])) {
            throw new CircularDependencyException("Circular dependency detected for '{$id}'");
        }

        $binding = $this->bindings[$id];

        // Return cached singleton instance
        if ($binding['type'] === 'singleton' && isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Resolve the service
        $this->resolving[$id] = true;

        try {
            $instance = ($binding['concrete'])($this);

            // Cache singleton
            if ($binding['type'] === 'singleton') {
                $this->instances[$id] = $instance;
            }

            return $instance;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * Checks if a service is registered.
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    /**
     * Clears all singleton instances (useful for testing).
     *
     * @return void
     */
    public function flush(): void
    {
        $this->instances = [];
        $this->resolving = [];
    }

    /**
     * Resets the container to a fresh state (useful for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

/**
 * Exception thrown when a service is not found.
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}

/**
 * Exception thrown when a circular dependency is detected.
 */
class CircularDependencyException extends \Exception implements ContainerExceptionInterface
{
}
