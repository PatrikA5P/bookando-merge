<?php

// ⚠️ Keine Namespace-Deklaration! (wichtig für globale Nutzung)

// Importiere Helper-Classes aus dem Core
use Bookando\Core\Helper\Icon;
use Bookando\Core\Helper\Languages;
use Bookando\Core\Helper\Locales;
use Bookando\Core\Service\ActivityLogger;
use Bookando\Core\Tenant\TenantManager;

// === DEBUG/DEV-Helper ===
if (defined('WP_DEBUG') && WP_DEBUG) {
    ActivityLogger::info('core.helpers', 'helpers.php erfolgreich geladen');
}

// === DEV-Check ===
if (!function_exists('bookando_is_dev')) {
    function bookando_is_dev(): bool {
        return defined('BOOKANDO_DEV') && BOOKANDO_DEV;
    }
}

// === TENANT-HELPER ===
if (!function_exists('get_current_tenant_id')) {
    function get_current_tenant_id(): int {
        return TenantManager::currentTenantId();
    }
}

// === ICON ===
if (!function_exists('bookando_icon')) {
    function bookando_icon(string $name, string $class = ''): string {
        return Icon::render($name, $class);
    }
}

// === SPRACH- & LOCALE-UTILS (Flatpickr, etc.) ===
if (!function_exists('language_label')) {
    function language_label(string $code): string {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            ActivityLogger::info('helper.languages', 'language_label() used', ['code' => $code]);
        }
        return Languages::label($code);
    }
}
if (!function_exists('available_languages')) {
    function available_languages(string $path = '', array $favorites = []): array {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            ActivityLogger::info('helper.languages', 'available_languages() called', ['path' => $path, 'favorites' => $favorites]);
        }
        return Languages::listAvailable($path, $favorites);
    }
}
if (!function_exists('available_locales')) {
    function available_locales(string $path = '', array $favorites = []): array {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            ActivityLogger::info('helper.locales', 'available_locales() called', ['path' => $path, 'favorites' => $favorites]);
        }
        return Locales::listAvailable($path, $favorites);
    }
}

// === UTILITIES ===
if (!function_exists('bookando_slugify')) {
    function bookando_slugify(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('bookando_format_price')) {
    function bookando_format_price($amount, string $currency = '€'): string {
        return number_format((float)$amount, 2, ',', '.') . ' ' . $currency;
    }
}

/**
 * Liest und sanitiert einen Request-Parameter aus $_REQUEST.
 *
 * Diese zentrale Funktion eliminiert Code-Duplikation zwischen:
 * - Menu.php::readNonce()
 * - BaseModule.php::readRequestString()
 *
 * @param string $key Der Request-Parameter-Key
 * @param bool $isNonce Wenn true, wird nur wp_unslash() verwendet (kein sanitize_text_field)
 * @return string Der sanitierte Wert oder leerer String
 */
if (!function_exists('bookando_read_sanitized_request')) {
    function bookando_read_sanitized_request(string $key, bool $isNonce = false): string {
        if (!isset($_REQUEST[$key])) {
            return '';
        }

        $raw = $_REQUEST[$key];

        // Arrays → erstes Element nehmen
        if (is_array($raw)) {
            $raw = reset($raw);
        }

        // Nur Strings verarbeiten
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        // Slashes entfernen (falls Magic Quotes aktiv)
        $value = function_exists('wp_unslash') ? wp_unslash($raw) : stripslashes($raw);

        // KRITISCH: Nonces dürfen NICHT mit sanitize_text_field() behandelt werden!
        // sanitize_text_field() kann den Nonce beschädigen und Redirect-Loops verursachen
        if ($isNonce) {
            return $value;
        }

        // Sanitize für normale Parameter
        return function_exists('sanitize_text_field')
            ? sanitize_text_field($value)
            : trim($value);
    }
}

// === WP-User Helper ===
if (!function_exists('bookando_current_user')) {
    function bookando_current_user() {
        return wp_get_current_user();
    }
}

// === BOOKANDO-USER-SPRACHE (MANDANTEN-/USER-SPEZIFISCH) ===
if (!function_exists('bookando_user_language')) {
    /**
     * Liefert die aktuelle Sprache des eingeloggten Bookando-Users (oder 'de' als Fallback)
     */
    function bookando_user_language(): string {
        global $wpdb;
        $wp_id = get_current_user_id();
        if (!$wp_id) return 'de';
        $p = $wpdb->prefix . 'bookando_';
        $lang = $wpdb->get_var($wpdb->prepare(
            "SELECT language FROM {$p}users WHERE external_id = %d", $wp_id
        ));
        return $lang ?: 'de';
    }
}

// === BOOKANDO-USER-ANLEGEN (WP-User zu Bookando-User Mappen) ===
if (!function_exists('bookando_get_or_create_bookando_user')) {
    /**
     * Gibt Bookando-User für eingeloggten WP-User zurück (legt ihn ggf. an).
     * Gibt DB-Objekt zurück oder NULL.
     */
    function bookando_get_or_create_bookando_user() {
        global $wpdb;
        $wp_id = get_current_user_id();
        if (!$wp_id) return null;
        $p = $wpdb->prefix . 'bookando_';
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$p}users WHERE external_id = %d", $wp_id
        ));
        if (!$user) {
            $wp_user = get_userdata($wp_id);
            if (!$wp_user) return null;
            $lang = get_user_locale($wp_id) ?: 'de';
            $roles = wp_json_encode(['bookando_customer']);
            if ($roles === false) {
                ActivityLogger::error('core.helpers', 'Failed to encode default roles for bookando user', [
                    'wp_user_id' => $wp_id,
                ]);
                $roles = '[]';
            }

            $wpdb->insert("{$p}users", [
                'external_id' => $wp_id,
                'email'       => $wp_user->user_email,
                'first_name'  => $wp_user->first_name ?: '',
                'last_name'   => $wp_user->last_name ?: '',
                'language'    => $lang,
                'status'      => 'active',
                'roles'       => $roles,
                'created_at'  => current_time('mysql')
            ]);
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$p}users WHERE external_id = %d", $wp_id
            ));
        }
        return $user;
    }
}
