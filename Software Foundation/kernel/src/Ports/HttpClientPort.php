<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Immutable value object representing an HTTP response.
 */
final class HttpResponse
{
    /**
     * @param int                  $statusCode HTTP status code (e.g. 200, 404, 500).
     * @param string               $body       Raw response body.
     * @param array<string, mixed> $headers    Response headers as key => value(s).
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers,
    ) {}
}

/**
 * Outgoing HTTP client port.
 *
 * Provides a host-agnostic interface for making external HTTP requests.
 * Implementations may use cURL, WordPress `wp_remote_request()`, Guzzle,
 * Symfony HttpClient, or any other HTTP library available in the host
 * environment.
 */
interface HttpClientPort
{
    /**
     * Send an HTTP request and return the response.
     *
     * @param string               $method  HTTP method (GET, POST, PUT, PATCH, DELETE, etc.).
     * @param string               $url     Fully-qualified URL to request.
     * @param array<string, mixed> $options Request options. Common keys include:
     *                                      - "headers" (array)  Request headers.
     *                                      - "body"    (string) Raw request body.
     *                                      - "json"    (array)  JSON-encoded body (sets Content-Type automatically).
     *                                      - "query"   (array)  Query-string parameters.
     *                                      - "timeout" (int)    Request timeout in seconds.
     *                                      - "auth"    (array)  [username, password] for Basic auth.
     *
     * @return HttpResponse The response received from the remote server.
     *
     * @throws \RuntimeException If the request could not be completed (network error, DNS failure, etc.).
     */
    public function request(string $method, string $url, array $options = []): HttpResponse;
}
