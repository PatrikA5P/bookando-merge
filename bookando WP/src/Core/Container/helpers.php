<?php

declare(strict_types=1);

use Bookando\Core\Container\Container;

if (!function_exists('container')) {
    /**
     * Gets the global container instance.
     *
     * @return Container
     */
    function container(): Container
    {
        return Container::getInstance();
    }
}

if (!function_exists('resolve')) {
    /**
     * Resolves a service from the container.
     *
     * @template T
     * @param class-string<T> $abstract
     * @return T
     */
    function resolve(string $abstract)
    {
        return container()->get($abstract);
    }
}
