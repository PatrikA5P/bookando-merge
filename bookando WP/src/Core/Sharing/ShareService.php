<?php
declare(strict_types=1);

namespace Bookando\Core\Sharing;

use wpdb;
use Bookando\Core\Tenant\TenantManager;

final class ShareService
{
    /**
     * Legt einen ACL-Eintrag an und gibt ein signiertes Token zurück.
     */
    public static function createShare(string $resourceType, int $resourceId, int $granteeTenant, string $scope = 'view', int $ttlMinutes = 0): array
    {
        global $wpdb;
        $owner = TenantManager::currentTenantId();
        $tbl   = $wpdb->prefix.'bookando_share_acl';

        $expiresAt = $ttlMinutes > 0 ? gmdate('Y-m-d H:i:s', time() + ($ttlMinutes * 60)) : null;

        // UPSERT-ähnlich: UNIQUE (resource_type, resource_id, grantee_tenant)
        $wpdb->replace($tbl, [
            'resource_type'  => $resourceType,
            'resource_id'    => $resourceId,
            'owner_tenant'   => $owner,
            'grantee_tenant' => $granteeTenant,
            'scope'          => $scope,
            'expires_at'     => $expiresAt,
            'created_at'     => current_time('mysql', 1),
        ]);

        // Token enthält nur Context; ACL ist Quelle der Wahrheit
        $payload = [
            'rt' => $resourceType,
            'ri' => $resourceId,
            'ot' => $owner,
            'gt' => $granteeTenant,
            'sc' => $scope,
            'xa' => $expiresAt ? strtotime($expiresAt) : null,
            'ts' => time(),
        ];
        $token = self::sign($payload);

        return [
            'ok'        => true,
            'token'     => $token,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Prüft Token + ACL. Rückgabe enthält aufgelöste Metadaten.
     */
    public static function resolveToken(string $token): array
    {
        $data = self::verify($token);
        if (!$data) return ['ok' => false];

        // Wenn eine Ablaufzeit im Token steht, zusätzlich prüfen
        if (!empty($data['xa']) && time() > (int)$data['xa']) {
            return ['ok' => false];
        }

        // ACL prüfen
        if (!TenantManager::canAccessShared($data['rt'], (int)$data['ri'], (int)$data['ot'])) {
            return ['ok' => false];
        }

        return [
            'ok'   => true,
            'type' => $data['rt'],
            'id'   => (int)$data['ri'],
            'owner_tenant'   => (int)$data['ot'],
            'grantee_tenant' => (int)$data['gt'],
            'scope'          => (string)$data['sc'],
            'expires_at'     => !empty($data['xa']) ? gmdate('c', (int)$data['xa']) : null,
        ];
    }

    // ---------------- internal helpers ----------------

    private static function secret(): string
    {
        // WP Secret Keys bevorzugen
        foreach (['AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $k) {
            if (defined($k) && constant($k)) return constant($k);
        }
        return wp_salt('auth');
    }

    private static function sign(array $payload): string
    {
        $json = wp_json_encode($payload);
        $b64  = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $mac  = hash_hmac('sha256', $b64, self::secret(), true);
        $sig  = rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');
        return $b64.'.'.$sig;
    }

    private static function verify(string $token): ?array
    {
        if (strpos($token, '.') === false) return null;
        [$b64, $sig] = explode('.', $token, 2);
        $mac  = hash_hmac('sha256', $b64, self::secret(), true);
        $sig2 = rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');
        if (!hash_equals($sig2, $sig)) return null;

        $json = base64_decode(strtr($b64, '-_', '+/'));
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }
}
