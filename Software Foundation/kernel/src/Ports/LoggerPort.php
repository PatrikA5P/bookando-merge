<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Structured logging port.
 *
 * Provides a host-agnostic interface for structured, levelled logging.
 * Implementations may delegate to Monolog, the WordPress error log, a
 * database-backed audit trail, or any other logging mechanism available in
 * the host environment.
 *
 * All log methods accept a `$context` array for structured data that
 * implementations SHOULD persist alongside the message. A correlation ID
 * may be set once per request to tie related log entries together.
 */
interface LoggerPort
{
    /**
     * Log a message at the given level.
     *
     * @param string               $level   Log level (e.g. "debug", "info", "warning", "error", "critical").
     * @param string               $message Human-readable log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function log(string $level, string $message, array $context = []): void;

    /**
     * Log an informational message.
     *
     * @param string               $message Human-readable log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a warning message.
     *
     * @param string               $message Human-readable log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log an error message.
     *
     * @param string               $message Human-readable log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function error(string $message, array $context = []): void;

    /**
     * Record an auditable action.
     *
     * Audit entries are typically persisted to a tamper-evident store and are
     * intended for compliance and security review purposes. The context SHOULD
     * include at minimum `user_id`, `tenant_id`, and any relevant entity IDs.
     *
     * @param string               $action  Short, machine-readable action identifier (e.g. "booking.created").
     * @param array<string, mixed> $context Structured audit data.
     */
    public function audit(string $action, array $context): void;

    /**
     * Set a correlation ID that will be attached to every subsequent log entry.
     *
     * Typically set once at the start of a request or job to enable tracing
     * across all log lines produced during that execution.
     *
     * @param string $id The correlation / trace ID.
     */
    public function setCorrelationId(string $id): void;
}
