<?php

namespace Bookando\Core\Admin;

class Menu
{
    private static array $moduleMenus = [];

    /**
     * Wird von Modulen genutzt, um ein Untermenü zu registrieren
     */
    public static function addModuleSubmenu(array $config): void
    {
        self::$moduleMenus[] = wp_parse_args($config, [
            'page_title'  => '',
            'menu_title'  => '',
            'capability'  => 'manage_options',
            'menu_slug'   => '',
            'callback'    => null,
            'module_slug' => null,
        ]);
    }

    /**
     * Wird vom Plugin bei admin_menu aufgerufen
     */
    public static function registerMenus(): void
    {
        add_menu_page(
            __('Bookando', 'bookando'),
            __('Bookando', 'bookando'),
            'manage_options',
            'bookando',
            [self::class, 'redirectToFirstSubmenu'],
            'dashicons-calendar-alt',
            25
        );

        // 🔁 Jetzt dürfen alle Module ihre Menüs registrieren
        do_action('bookando_register_module_menus');

        foreach (self::$moduleMenus as $menu) {
            $hookSuffix = add_submenu_page(
                'bookando',
                $menu['page_title'],
                $menu['menu_title'],
                $menu['capability'],
                $menu['menu_slug'],
                $menu['callback']
            );

            if (
                is_string($hookSuffix)
                && isset($menu['module_slug'])
                && is_string($menu['module_slug'])
                && $menu['module_slug'] !== ''
            ) {
                self::ensureModuleNonce($hookSuffix, $menu['module_slug'], $menu['menu_slug']);
            }
        }

        // 🚩 HIER: Dummy-Submenü (Bookando) entfernen, wie es alle großen Plugins machen!
        global $submenu;
        if (isset($submenu['bookando'])) {
            foreach ($submenu['bookando'] as $k => $item) {
                // Prüfe ob Menüslug == "bookando" (das ist das Dummy-Submenü)
                if (isset($item[2]) && $item[2] === 'bookando') {
                    unset($submenu['bookando'][$k]);
                }
            }
            // Optional: Neu indizieren (Schönheit/Konsistenz)
            $submenu['bookando'] = array_values($submenu['bookando']);
        }
    }

    /**
     * Leitet beim Klick auf "Bookando" immer auf das erste aktivierte Modul weiter
     */
    public static function redirectToFirstSubmenu()
    {
        global $plugin_page;
        $submenu = $GLOBALS['submenu']['bookando'] ?? [];
        if (!empty($submenu)) {
            $first = $submenu[0][2] ?? null;
            if ($first && $plugin_page !== $first) {
                echo "<script>window.location.href='" . esc_url(admin_url('admin.php?page=' . $first)) . "';</script>";
                exit;
            }
        }
        echo '<h1>' . esc_html__('Bookando', 'bookando') . '</h1><p>' . esc_html__('Kein Modul aktiviert.', 'bookando') . '</p>';
    }

    private static function ensureModuleNonce(string $hookSuffix, $moduleSlug, $menuSlug): void
    {
        $moduleSlug = is_string($moduleSlug) ? sanitize_key($moduleSlug) : '';
        $menuSlug   = is_string($menuSlug) ? $menuSlug : '';

        if ($moduleSlug === '' || $menuSlug === '') {
            return;
        }

        add_action('load-' . $hookSuffix, static function () use ($moduleSlug, $menuSlug): void {
            // Debug-Logging initialisieren
            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::init();
                \Bookando\Core\Service\DebugLogger::startTimer('nonce_check');
                \Bookando\Core\Service\DebugLogger::logNonce('ensureModuleNonce_start', [
                    'module_slug' => $moduleSlug,
                    'menu_slug' => $menuSlug,
                    'hook_suffix' => 'load-' . $moduleSlug,
                ]);
            }

            if (!function_exists('wp_create_nonce') || !function_exists('wp_verify_nonce')) {
                if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                    \Bookando\Core\Service\DebugLogger::log('❌ WordPress nonce functions not available');
                }
                return;
            }

            $action = "bookando_module_assets_{$moduleSlug}";
            $nonce  = self::readNonce();

            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::logNonce('nonce_read', [
                    'action' => $action,
                    'nonce_empty' => $nonce === '',
                    'nonce_length' => strlen($nonce),
                    'nonce_preview' => $nonce !== '' ? substr($nonce, 0, 10) . '...' : 'EMPTY',
                ]);
            }

            // Nonce-Verifikation
            $verifyResult = false;
            if ($nonce !== '') {
                $verifyResult = wp_verify_nonce($nonce, $action);

                if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                    \Bookando\Core\Service\DebugLogger::logNonce('nonce_verify', [
                        'action' => $action,
                        'result' => $verifyResult ? 'VALID' : 'INVALID',
                        'nonce_age' => $verifyResult,
                    ]);
                }
            }

            if ($nonce !== '' && $verifyResult) {
                if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                    \Bookando\Core\Service\DebugLogger::stopTimer('nonce_check');
                    \Bookando\Core\Service\DebugLogger::logNonce('nonce_valid', [
                        'action' => $action,
                        'status' => 'Proceeding without redirect',
                    ]);
                }
                return; // Nonce ist gültig, kein Redirect
            }

            // Nonce ist ungültig oder nicht vorhanden → Redirect
            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::logNonce('nonce_invalid_redirect_needed', [
                    'action' => $action,
                    'nonce_present' => $nonce !== '',
                    'verify_result' => $verifyResult,
                ]);
            }

            $target = self::buildModulePageUrl($menuSlug);
            if ($target === null) {
                if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                    \Bookando\Core\Service\DebugLogger::log('❌ buildModulePageUrl returned null');
                }
                return;
            }

            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::logNonce('building_redirect_url', [
                    'target' => $target,
                    'action' => $action,
                ]);
            }

            if (!function_exists('wp_nonce_url') || !function_exists('wp_safe_redirect')) {
                if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                    \Bookando\Core\Service\DebugLogger::log('❌ wp_nonce_url or wp_safe_redirect not available');
                }
                return;
            }

            $redirect = wp_nonce_url($target, $action);

            if (!is_string($redirect) || $redirect === '') {
                if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                    \Bookando\Core\Service\DebugLogger::log('❌ wp_nonce_url returned invalid value', [
                        'type' => gettype($redirect),
                        'value' => $redirect,
                    ]);
                }
                return;
            }

            // KRITISCHER FIX: wp_nonce_url() gibt HTML-encodierte URLs zurück (&amp;)
            // wp_safe_redirect() benötigt aber echte URLs mit &
            // Ohne Dekodierung entsteht ein Redirect-Loop, weil der Browser
            // die URL mit &amp;_wpnonce=... nicht korrekt parsen kann
            $redirect = html_entity_decode($redirect, ENT_QUOTES | ENT_HTML5);

            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::logNonce('redirect_executing', [
                    'redirect_url' => $redirect,
                    'action' => $action,
                ]);
                \Bookando\Core\Service\DebugLogger::stopTimer('nonce_check');
            }

            wp_safe_redirect($redirect);
            exit;
        });
    }

    /**
     * Liest den Nonce aus dem Request.
     *
     * Nutzt die zentrale Helper-Funktion bookando_read_sanitized_request()
     * um Code-Duplikation zu vermeiden.
     *
     * WICHTIG: Der zweite Parameter ($isNonce = true) verhindert, dass
     * sanitize_text_field() den Nonce beschädigt, was zu Redirect-Loops führen würde.
     *
     * @return string
     */
    private static function readNonce(): string
    {
        return bookando_read_sanitized_request('_wpnonce', true);
    }

    private static function buildModulePageUrl(string $menuSlug): ?string
    {
        if (!function_exists('menu_page_url')) {
            return null;
        }

        $baseUrl = menu_page_url($menuSlug, false);
        if (!is_string($baseUrl) || $baseUrl === '') {
            return null;
        }

        // KRITISCHER FIX: Entferne alte _wpnonce Parameter aus der URL
        //
        // ROOT CAUSE des Redirect-Loops:
        // 1. menu_page_url() gibt die AKTUELLE URL zurück
        // 2. Diese enthält bereits "&amp;_wpnonce=..." vom vorherigen Redirect
        // 3. PHP kann "&amp;_wpnonce" NICHT als URL-Parameter parsen (HTML-Entity!)
        // 4. Deshalb ist $_GET['_wpnonce'] leer, aber der String bleibt im URL
        // 5. Unten wird $_GET['_wpnonce'] gefiltert, aber das hilft nicht,
        //    weil der Parameter als STRING im $baseUrl steckt!
        // 6. Result: &amp;_wpnonce wird immer wieder weitergegeben → Redirect-Loop!
        //
        // Lösung in 2 Schritten (REIHENFOLGE IST KRITISCH!):
        // 1. ERST html_entity_decode() - wandelt &amp; zu & um
        // 2. DANN remove_query_arg() - kann jetzt &_wpnonce= finden und entfernen
        $originalUrl = $baseUrl;

        // Schritt 1: Decode HTML entities (&amp; → &)
        $baseUrl = html_entity_decode($baseUrl, ENT_QUOTES | ENT_HTML5);

        // Schritt 2: Remove old nonce from URL string
        if (function_exists('remove_query_arg')) {
            $baseUrl = remove_query_arg(['_wpnonce', '_wp_http_referer'], $baseUrl);
        }

        if (class_exists(\Bookando\Core\Service\DebugLogger::class) && $originalUrl !== $baseUrl) {
            \Bookando\Core\Service\DebugLogger::log('🔧 Cleaned URL (decoded + removed old nonce)', [
                'original' => $originalUrl,
                'cleaned' => $baseUrl,
            ]);
        }

        $queryArgs = [];

        foreach ($_GET as $key => $value) {
            if (!is_string($key) || $key === '' || $key === '_wpnonce' || $key === '_wp_http_referer') {
                continue;
            }

            if ($key === 'page') {
                continue;
            }

            $queryArgs[$key] = self::sanitizeQueryArg($value);
        }

        if (!empty($queryArgs) && function_exists('add_query_arg')) {
            $baseUrl = add_query_arg($queryArgs, $baseUrl);
        }

        return $baseUrl;
    }

    private static function sanitizeQueryArg($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeQueryArg'], $value);
        }

        if (is_scalar($value)) {
            $value = (string) $value;
            $value = function_exists('wp_unslash') ? wp_unslash($value) : $value;

            return function_exists('sanitize_text_field') ? sanitize_text_field($value) : $value;
        }

        return '';
    }
}
