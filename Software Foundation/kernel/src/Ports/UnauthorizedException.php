<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Exception thrown when the current user lacks the required permission.
 *
 * This exception is raised by {@see AuthorizationPort::authorize()} and may
 * be caught by middleware or error handlers to produce an appropriate HTTP
 * 403 response or equivalent access-denied feedback.
 */
final class UnauthorizedException extends \RuntimeException
{
    /**
     * @param string          $permission The permission that was required but not held.
     * @param int             $code       Optional exception code.
     * @param \Throwable|null $previous   Optional previous exception for chaining.
     */
    public function __construct(
        string $permission,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $message = sprintf('Unauthorized: missing required permission "%s".', $permission);
        parent::__construct($message, $code, $previous);
    }
}
