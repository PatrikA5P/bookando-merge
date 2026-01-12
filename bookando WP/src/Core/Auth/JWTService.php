<?php
declare(strict_types=1);

namespace Bookando\Core\Auth;

use WP_Error;
use WP_User;

/**
 * JWT Authentication Service für Mobile Apps und SaaS-Integration.
 *
 * Unterstützt:
 * - Token-Generierung mit konfigurierbarer TTL
 * - Token-Validierung mit Signatur-Prüfung
 * - Refresh-Token-Mechanismus
 * - Token-Revocation via Blacklist
 *
 * Token-Format: Base64URL(Header).Base64URL(Payload).Signature
 *
 * Payload enthält:
 * - iss: Issuer (WordPress Site URL)
 * - sub: Subject (WordPress User ID)
 * - tid: Tenant ID
 * - iat: Issued At (Unix Timestamp)
 * - exp: Expiration (Unix Timestamp)
 * - jti: JWT ID (eindeutige Token-ID)
 */
final class JWTService
{
    /** Token-Typ im Header */
    private const TOKEN_TYPE = 'JWT';

    /** Hash-Algorithmus */
    private const ALGORITHM = 'HS256';

    /** Standard-TTL: 24 Stunden */
    private const DEFAULT_TTL = 86400;

    /** Refresh-Token-TTL: 30 Tage */
    private const REFRESH_TTL = 2592000;

    /** Leeway für Clock-Skew: 60 Sekunden */
    private const LEEWAY = 60;

    /**
     * Generiert ein JWT-Token für einen WordPress-User.
     *
     * @param int $userId WordPress User ID
     * @param int $tenantId Tenant ID
     * @param int $ttl Time-to-Live in Sekunden
     * @param array $customClaims Zusätzliche Claims (z.B. ['scope' => 'api:write'])
     * @return array ['token' => string, 'expires_at' => int, 'jti' => string]
     */
    public static function generateToken(int $userId, int $tenantId, int $ttl = self::DEFAULT_TTL, array $customClaims = []): array
    {
        $now = time();
        $exp = $now + $ttl;
        $jti = self::generateJTI();

        // Standard-Claims (JWT RFC 7519)
        $payload = [
            'iss' => home_url(),                    // Issuer
            'sub' => (string) $userId,              // Subject (User ID)
            'tid' => $tenantId,                     // Tenant ID (Custom Claim)
            'iat' => $now,                          // Issued At
            'exp' => $exp,                          // Expiration
            'jti' => $jti,                          // JWT ID (für Revocation)
        ];

        // Merge custom claims
        $payload = array_merge($payload, $customClaims);

        $token = self::encode($payload);

        return [
            'token' => $token,
            'expires_at' => $exp,
            'jti' => $jti,
        ];
    }

    /**
     * Generiert ein Refresh-Token (längere TTL).
     *
     * @param int $userId WordPress User ID
     * @param int $tenantId Tenant ID
     * @return array ['token' => string, 'expires_at' => int, 'jti' => string]
     */
    public static function generateRefreshToken(int $userId, int $tenantId): array
    {
        return self::generateToken($userId, $tenantId, self::REFRESH_TTL, ['type' => 'refresh']);
    }

    /**
     * Validiert und dekodiert ein JWT-Token.
     *
     * @param string $token JWT-Token
     * @return array|WP_Error Payload-Array oder WP_Error bei Fehler
     */
    public static function validateToken(string $token)
    {
        // Token-Format prüfen
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return new WP_Error(
                'jwt_invalid_format',
                __('JWT token has invalid format.', 'bookando'),
                ['status' => 401]
            );
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Signatur verifizieren
        $expectedSignature = self::sign($headerB64 . '.' . $payloadB64);
        if (!hash_equals($expectedSignature, $signatureB64)) {
            return new WP_Error(
                'jwt_invalid_signature',
                __('JWT token signature verification failed.', 'bookando'),
                ['status' => 401]
            );
        }

        // Header dekodieren
        $header = json_decode(self::base64UrlDecode($headerB64), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== self::ALGORITHM) {
            return new WP_Error(
                'jwt_unsupported_algorithm',
                __('JWT token uses unsupported algorithm.', 'bookando'),
                ['status' => 401]
            );
        }

        // Payload dekodieren
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) {
            return new WP_Error(
                'jwt_invalid_payload',
                __('JWT token payload is invalid.', 'bookando'),
                ['status' => 401]
            );
        }

        // Expiration prüfen (mit Leeway für Clock-Skew)
        if (isset($payload['exp']) && time() > ($payload['exp'] + self::LEEWAY)) {
            return new WP_Error(
                'jwt_expired',
                __('JWT token has expired.', 'bookando'),
                ['status' => 401]
            );
        }

        // Issued-At prüfen (Token nicht aus der Zukunft)
        if (isset($payload['iat']) && ($payload['iat'] - self::LEEWAY) > time()) {
            return new WP_Error(
                'jwt_future_token',
                __('JWT token issued in the future.', 'bookando'),
                ['status' => 401]
            );
        }

        // Issuer prüfen
        if (isset($payload['iss']) && $payload['iss'] !== home_url()) {
            return new WP_Error(
                'jwt_invalid_issuer',
                __('JWT token issuer mismatch.', 'bookando'),
                ['status' => 401]
            );
        }

        // Revocation-Check (Blacklist)
        if (isset($payload['jti']) && self::isRevoked($payload['jti'])) {
            return new WP_Error(
                'jwt_revoked',
                __('JWT token has been revoked.', 'bookando'),
                ['status' => 401]
            );
        }

        return $payload;
    }

    /**
     * Revoke (sperrt) ein Token via Blacklist.
     *
     * @param string $jti JWT ID
     * @param int $ttl TTL der Blacklist-Entry (sollte = Token-TTL sein)
     * @return void
     */
    public static function revokeToken(string $jti, int $ttl = self::DEFAULT_TTL): void
    {
        // Transient-basierte Blacklist (für einfache Setups)
        // Bei hoher Last: Redis oder dedizierte Tabelle verwenden
        $key = 'bookando_jwt_blacklist_' . md5($jti);
        set_transient($key, 1, $ttl);
    }

    /**
     * Prüft, ob ein Token revoked (gesperrt) ist.
     *
     * @param string $jti JWT ID
     * @return bool
     */
    public static function isRevoked(string $jti): bool
    {
        $key = 'bookando_jwt_blacklist_' . md5($jti);
        return (bool) get_transient($key);
    }

    /**
     * Extrahiert User-ID aus Token-Payload.
     *
     * @param array $payload Validiertes Token-Payload
     * @return int User ID oder 0 bei Fehler
     */
    public static function getUserId(array $payload): int
    {
        return isset($payload['sub']) ? (int) $payload['sub'] : 0;
    }

    /**
     * Extrahiert Tenant-ID aus Token-Payload.
     *
     * @param array $payload Validiertes Token-Payload
     * @return int Tenant ID oder 0 bei Fehler
     */
    public static function getTenantId(array $payload): int
    {
        return isset($payload['tid']) ? (int) $payload['tid'] : 0;
    }

    /**
     * Generiert ein neues Token mit einem Refresh-Token.
     *
     * @param string $refreshToken Refresh-Token
     * @return array|WP_Error ['access_token' => ..., 'refresh_token' => ..., 'expires_in' => ...]
     */
    public static function refreshAccessToken(string $refreshToken)
    {
        $payload = self::validateToken($refreshToken);

        if ($payload instanceof WP_Error) {
            return $payload;
        }

        // Prüfen, ob es tatsächlich ein Refresh-Token ist
        if (($payload['type'] ?? 'access') !== 'refresh') {
            return new WP_Error(
                'jwt_invalid_refresh_token',
                __('Token is not a refresh token.', 'bookando'),
                ['status' => 400]
            );
        }

        $userId = self::getUserId($payload);
        $tenantId = self::getTenantId($payload);

        // User existiert noch?
        $user = get_user_by('ID', $userId);
        if (!$user) {
            return new WP_Error(
                'jwt_user_not_found',
                __('User no longer exists.', 'bookando'),
                ['status' => 404]
            );
        }

        // Neues Access-Token generieren
        $accessToken = self::generateToken($userId, $tenantId);

        // Neues Refresh-Token generieren
        $newRefreshToken = self::generateRefreshToken($userId, $tenantId);

        // Altes Refresh-Token revoken
        if (isset($payload['jti'])) {
            self::revokeToken($payload['jti'], self::REFRESH_TTL);
        }

        return [
            'access_token' => $accessToken['token'],
            'refresh_token' => $newRefreshToken['token'],
            'expires_in' => self::DEFAULT_TTL,
            'token_type' => 'Bearer',
        ];
    }

    // -------------------- Private Helper Methods --------------------

    /**
     * Encodiert Payload als JWT-Token.
     */
    private static function encode(array $payload): string
    {
        $header = [
            'typ' => self::TOKEN_TYPE,
            'alg' => self::ALGORITHM,
        ];

        $headerB64 = self::base64UrlEncode(wp_json_encode($header));
        $payloadB64 = self::base64UrlEncode(wp_json_encode($payload));

        $signature = self::sign($headerB64 . '.' . $payloadB64);

        return $headerB64 . '.' . $payloadB64 . '.' . $signature;
    }

    /**
     * Signiert einen String mit HMAC-SHA256.
     */
    private static function sign(string $data): string
    {
        $secret = self::getSecret();
        $hash = hash_hmac('sha256', $data, $secret, true);
        return self::base64UrlEncode($hash);
    }

    /**
     * Liefert das Secret für Token-Signierung.
     * Verwendet WordPress Auth-Salts.
     */
    private static function getSecret(): string
    {
        // Kombiniere mehrere WP Salts für maximale Entropie
        $salts = [];
        foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY'] as $key) {
            if (defined($key)) {
                $salts[] = constant($key);
            }
        }

        if (empty($salts)) {
            // Fallback: wp_salt() (sollte nie passieren)
            return wp_salt('auth');
        }

        return implode('', $salts);
    }

    /**
     * Base64URL-Encoding (RFC 4648).
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL-Decoding (RFC 4648).
     */
    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generiert eine eindeutige JWT-ID (JTI).
     * Format: timestamp_random
     */
    private static function generateJTI(): string
    {
        return time() . '_' . bin2hex(random_bytes(16));
    }
}
