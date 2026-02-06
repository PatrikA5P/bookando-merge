<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * File / blob storage port.
 *
 * Provides a host-agnostic interface for storing and retrieving files or
 * binary blobs. Implementations may use the local filesystem, WordPress
 * uploads directory, Amazon S3, or any other storage backend available in
 * the host environment.
 *
 * Paths are logical and relative (e.g. "invoices/2026/inv-001.pdf").
 * The implementation is responsible for resolving these to absolute paths
 * or object keys.
 */
interface StoragePort
{
    /**
     * Store contents at the given path.
     *
     * If a file already exists at the path it is overwritten.
     *
     * @param string $path     Logical file path.
     * @param string $contents Raw file contents.
     *
     * @return bool True on success, false on failure.
     */
    public function put(string $path, string $contents): bool;

    /**
     * Retrieve the contents of a file.
     *
     * @param string $path Logical file path.
     *
     * @return string|null The file contents, or null if the file does not exist.
     */
    public function get(string $path): ?string;

    /**
     * Delete a file.
     *
     * @param string $path Logical file path.
     *
     * @return bool True if the file was deleted (or did not exist), false on failure.
     */
    public function delete(string $path): bool;

    /**
     * Check whether a file exists at the given path.
     *
     * @param string $path Logical file path.
     *
     * @return bool True if the file exists.
     */
    public function exists(string $path): bool;

    /**
     * Return a publicly-accessible URL for the given file.
     *
     * @param string $path Logical file path.
     *
     * @return string The public URL.
     */
    public function url(string $path): string;
}
