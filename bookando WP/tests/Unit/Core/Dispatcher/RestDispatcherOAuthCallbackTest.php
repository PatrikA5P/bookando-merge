<?php

namespace Bookando\Tests\Unit\Core\Dispatcher;

use Bookando\Core\Dispatcher\RestDispatcher;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RestDispatcherOAuthCallbackTest extends TestCase
{
    private \Bookando_Test_SpyWpdb $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        bookando_test_reset_stubs();
        $this->wpdb = new \Bookando_Test_SpyWpdb();
        $this->wpdb->prefix = 'wp_';
        $this->wpdb->registerLookup('wp_bookando_activity_log', 'wp_bookando_activity_log');
        $GLOBALS['wpdb'] = $this->wpdb;

        if (!defined('BOOKANDO_GOOGLE_CLIENT_ID')) {
            define('BOOKANDO_GOOGLE_CLIENT_ID', 'client-id');
        }
        if (!defined('BOOKANDO_GOOGLE_CLIENT_SECRET')) {
            define('BOOKANDO_GOOGLE_CLIENT_SECRET', 'client-secret');
        }
    }

    protected function tearDown(): void
    {
        RestDispatcher::setTokenPersistenceCallback(null);
        bookando_test_mock_http_post(null);
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    public function testCallbackPersistsTokensAndReturnsSuccess(): void
    {
        $state = 'test-state';
        set_transient('bookando_oauth_state_' . $state, [
            'provider'    => 'google',
            'employee_id' => 77,
            'mode'        => 'ro',
            'created_at'  => time(),
        ], 900);

        bookando_test_mock_http_post(static function (string $url, array $args): array {
            return [
                'body'     => wp_json_encode([
                    'access_token'  => 'token-value',
                    'refresh_token' => 'refresh-token',
                    'expires_in'    => 3600,
                ]),
                'response' => ['code' => 200],
            ];
        });

        $captured = null;
        RestDispatcher::setTokenPersistenceCallback(static function (int $employeeId, string $provider, array $payload, string $mode) use (&$captured): void {
            $captured = [$employeeId, $provider, $payload, $mode];
        });

        $request = new WP_REST_Request('GET', '/bookando/v1/integrations/oauth/callback');
        $request->set_param('code', 'auth-code');
        $request->set_param('state', $state);

        $response = RestDispatcher::oauthCallback($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $this->assertIsArray($captured);
        [$employeeId, $provider, $payload, $mode] = $captured;
        $this->assertSame(77, $employeeId);
        $this->assertSame('google', $provider);
        $this->assertSame('ro', $mode);
        $this->assertSame('token-value', $payload['access_token']);

        $lastHttp = bookando_test_get_last_http_post();
        $this->assertSame('https://oauth2.googleapis.com/token', $lastHttp['url']);
        $this->assertStringContainsString('code=auth-code', (string) $lastHttp['args']['body']);
    }

    public function testCallbackReturnsErrorWhenPersistenceFails(): void
    {
        $state = 'fail-state';
        set_transient('bookando_oauth_state_' . $state, [
            'provider'    => 'google',
            'employee_id' => 88,
            'mode'        => 'ro',
            'created_at'  => time(),
        ], 900);

        bookando_test_mock_http_post(static function (string $url, array $args): array {
            return [
                'body'     => wp_json_encode([
                    'access_token' => 'token-value',
                ]),
                'response' => ['code' => 200],
            ];
        });

        RestDispatcher::setTokenPersistenceCallback(static function (): void {
            throw new \RuntimeException('boom');
        });

        $request = new WP_REST_Request('GET', '/bookando/v1/integrations/oauth/callback');
        $request->set_param('code', 'auth-code');
        $request->set_param('state', $state);

        $result = RestDispatcher::oauthCallback($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('oauth_persist_failed', $result->get_error_code());
        $this->assertSame(500, $result->get_error_data()['status']);
    }
}
