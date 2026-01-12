<?php

namespace Bookando\Tests\Unit\Core\Api;

use Bookando\Core\Api\Response;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Response;

class ResponseTest extends TestCase
{
    public function test_ok_wraps_payload_and_meta(): void
    {
        $response = Response::ok(['foo' => 'bar'], ['page' => 2]);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([
            'data' => ['foo' => 'bar'],
            'meta' => ['success' => true, 'page' => 2],
        ], $response->get_data());
    }

    public function test_created_sets_status_201(): void
    {
        $response = Response::created(['id' => 5]);

        $this->assertSame(201, $response->get_status());
        $this->assertSame([
            'data' => ['id' => 5],
            'meta' => ['success' => true],
        ], $response->get_data());
    }

    public function test_error_from_wp_error_uses_status_and_details(): void
    {
        $error    = new WP_Error('invalid_payload', 'Invalid payload.', ['status' => 422, 'reason' => 'format']);
        $response = Response::error($error, 400);

        $this->assertSame(422, $response->get_status());
        $this->assertSame([
            'data'  => null,
            'error' => [
                'code'    => 'invalid_payload',
                'message' => 'Invalid payload.',
                'details' => ['status' => 422, 'reason' => 'format'],
            ],
            'meta'  => ['success' => false, 'status' => 422],
        ], $response->get_data());
    }

    public function test_error_from_array_merges_meta(): void
    {
        $response = Response::error(
            [
                'code'    => 'forbidden',
                'message' => 'Access denied.',
                'meta'    => ['request_id' => 'abc-123'],
            ],
            403,
            ['origin' => 'unit-test']
        );

        $this->assertSame(403, $response->get_status());
        $this->assertSame([
            'data'  => null,
            'error' => [
                'code'    => 'forbidden',
                'message' => 'Access denied.',
            ],
            'meta'  => [
                'success'    => false,
                'status'     => 403,
                'origin'     => 'unit-test',
                'request_id' => 'abc-123',
            ],
        ], $response->get_data());
    }

    public function test_no_content_returns_empty_body(): void
    {
        $response = Response::noContent(['request_id' => 'ignored']);

        $this->assertSame(204, $response->get_status());
        $this->assertNull($response->get_data());
    }
}
