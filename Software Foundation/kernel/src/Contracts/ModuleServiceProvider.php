<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Contracts;

use Psr\Container\ContainerInterface;

/**
 * Every module MUST implement this to register its services.
 */
interface ModuleServiceProvider
{
    /** Register bindings into the container. Called once at boot. */
    public function register(ContainerInterface $container): void;

    /** Boot logic after all modules are registered. */
    public function boot(ContainerInterface $container): void;
}
