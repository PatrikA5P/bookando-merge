<?php

namespace Bookando\Tests\Unit\Core\Service;

use Bookando\Core\Service\OAuthTokenStorage;
use PHPUnit\Framework\TestCase;

final class OAuthTokenStorageTest extends TestCase
{
    private \Bookando_Test_SpyWpdb $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        bookando_test_reset_stubs();
        $this->wpdb = new \Bookando_Test_SpyWpdb();
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $this->wpdb;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    public function testPersistInsertsEncryptedTokens(): void
    {
        OAuthTokenStorage::persist(42, 'google', [
            'access_token'  => 'access-123',
            'refresh_token' => 'refresh-456',
            'expires_in'    => 3600,
            'email'         => 'Test@Example.com',
        ], 'wb');

        $this->assertCount(1, $this->wpdb->inserted);
        $insert = $this->wpdb->inserted[0];
        $this->assertSame('wp_bookando_calendar_connections', $insert['table']);

        $data = $insert['data'];
        $this->assertSame(42, $data['user_id']);
        $this->assertSame('google', $data['provider']);
        $this->assertSame('rw', $data['scope']);
        $this->assertNotSame('access-123', $data['access_token']);
        $this->assertNotSame('refresh-456', $data['refresh_token']);
        $this->assertSame('access-123', OAuthTokenStorage::decryptToken($data['access_token'], 42));
        $this->assertSame('refresh-456', OAuthTokenStorage::decryptToken($data['refresh_token'], 42));
        $this->assertSame('test@example.com', $data['account_email']);

        $meta = json_decode((string) $data['meta'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('wb', $meta['mode']);
    }

    public function testPersistUpdatesExistingConnection(): void
    {
        $this->wpdb->registerLookup('42', 99);

        OAuthTokenStorage::persist(42, 'google', [
            'access_token' => 'access-xyz',
        ], 'ro');

        $this->assertCount(1, $this->wpdb->updated);
        $update = $this->wpdb->updated[0];
        $this->assertSame('wp_bookando_calendar_connections', $update['table']);
        $this->assertSame(['id' => 99], $update['where']);
        $this->assertArrayHasKey('access_token', $update['data']);
        $this->assertSame('ro', $update['data']['scope']);
    }

    public function testPersistRejectsInvalidEmployee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OAuthTokenStorage::persist(0, 'google', [], 'ro');
    }
}
