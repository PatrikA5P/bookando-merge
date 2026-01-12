<?php
declare(strict_types=1);

namespace Bookando\Core\Service;

use RuntimeException;
use wpdb;

final class OAuthTokenStorage
{
    public static function persist(int $employeeId, string $provider, array $tokens, string $mode = 'ro'): void
    {
        if ($employeeId <= 0) {
            throw new \InvalidArgumentException('Employee identifier must be greater than zero.');
        }

        $provider = self::normalizeProvider($provider);
        if ($provider === '') {
            throw new \InvalidArgumentException('OAuth provider must not be empty.');
        }

        $scope = in_array($mode, ['wb', 'rw'], true) ? 'rw' : 'ro';

        $accessToken  = isset($tokens['access_token']) ? (string) $tokens['access_token'] : '';
        $refreshToken = isset($tokens['refresh_token']) ? (string) $tokens['refresh_token'] : '';

        $meta = $tokens;
        unset($meta['access_token'], $meta['refresh_token']);
        $meta['mode'] = $mode;

        $metaJson = $meta !== [] ? wp_json_encode($meta) : null;
        if ($metaJson === false) {
            $metaJson = null;
        }

        $expiresAt = self::resolveExpiry($tokens);
        $accountEmail = self::resolveAccountEmail($tokens);

        $encryptedAccess  = $accessToken !== '' ? self::encryptToken($accessToken, $employeeId) : null;
        $encryptedRefresh = $refreshToken !== '' ? self::encryptToken($refreshToken, $employeeId) : null;

        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            throw new RuntimeException('Database connection unavailable.');
        }

        $table = $wpdb->prefix . 'bookando_calendar_connections';
        $now   = current_time('mysql');

        $data = [
            'scope'          => $scope,
            'auth_type'      => 'oauth',
            'access_token'   => $encryptedAccess,
            'refresh_token'  => $encryptedRefresh,
            'expires_at'     => $expiresAt,
            'account_email'  => $accountEmail,
            'meta'           => $metaJson,
            'updated_at'     => $now,
        ];

        $existingId = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND provider = %s LIMIT 1",
                $employeeId,
                $provider
            )
        );

        if ($existingId > 0) {
            $result = $wpdb->update($table, $data, ['id' => $existingId], null, ['%d']);
            if ($result === false) {
                throw new RuntimeException($wpdb->last_error ?: 'Failed to update OAuth token.');
            }

            return;
        }

        $data += [
            'user_id'    => $employeeId,
            'provider'   => $provider,
            'created_at' => $now,
        ];

        $result = $wpdb->insert($table, $data);
        if ($result === false) {
            throw new RuntimeException($wpdb->last_error ?: 'Failed to store OAuth token.');
        }
    }

    public static function decryptToken(?string $payload, int $employeeId): ?string
    {
        if ($payload === null) {
            return null;
        }

        if ($payload === '') {
            return '';
        }

        [$iv, $tag, $ciphertext] = self::decodeEncryptedPayload($payload) ?? [null, null, null];
        if ($iv === null || $tag === null || $ciphertext === null) {
            return null;
        }

        $key = self::encryptionKey($employeeId);
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plaintext === false ? null : $plaintext;
    }

    private static function resolveExpiry(array $tokens): ?string
    {
        if (!empty($tokens['expires_at'])) {
            $timestamp = strtotime((string) $tokens['expires_at']);
            if ($timestamp !== false) {
                return gmdate('Y-m-d H:i:s', $timestamp);
            }
        }

        if (!empty($tokens['expires_in'])) {
            $seconds = (int) $tokens['expires_in'];
            if ($seconds > 0) {
                return gmdate('Y-m-d H:i:s', time() + $seconds);
            }
        }

        return null;
    }

    private static function resolveAccountEmail(array $tokens): ?string
    {
        $candidates = [
            $tokens['account_email'] ?? null,
            $tokens['email'] ?? null,
            is_array($tokens['user'] ?? null) ? (($tokens['user']['email'] ?? null)) : null,
        ];

        foreach ($candidates as $candidate) {
            $email = self::sanitizeEmail($candidate);
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    private static function sanitizeEmail(mixed $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }

        $email = trim(strtolower($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return substr($email, 0, 190);
    }

    private static function encryptToken(string $value, int $employeeId): string
    {
        $key = self::encryptionKey($employeeId);
        $iv  = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('Unable to encrypt OAuth token.');
        }

        return self::encodeEncryptedPayload($iv, $tag, $ciphertext);
    }

    private static function encryptionKey(int $employeeId): string
    {
        $base = self::baseSecret();
        $info = 'bookando|oauth|' . $employeeId;

        return hash_hmac('sha256', $info, $base, true);
    }

    private static function baseSecret(): string
    {
        foreach ([
            'BOOKANDO_ENCRYPTION_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
        ] as $constant) {
            if (defined($constant) && constant($constant)) {
                return hash('sha256', (string) constant($constant), true);
            }
        }

        if (function_exists('wp_salt')) {
            $salt = wp_salt('auth');
            if (is_string($salt) && $salt !== '') {
                return hash('sha256', $salt, true);
            }
        }

        return hash('sha256', 'bookando-default-secret', true);
    }

    private static function decodeEncryptedPayload(string $payload): ?array
    {
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false || strlen($decoded) <= 28) {
            return null;
        }

        $iv        = substr($decoded, 0, 12);
        $tag       = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        if ($iv === false || $tag === false || $ciphertext === false) {
            return null;
        }

        return [$iv, $tag, $ciphertext];
    }

    private static function encodeEncryptedPayload(string $iv, string $tag, string $ciphertext): string
    {
        return rtrim(strtr(base64_encode($iv . $tag . $ciphertext), '+/', '-_'), '=');
    }

    private static function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        if ($provider === 'outlook') {
            return 'microsoft';
        }

        return $provider;
    }
}
