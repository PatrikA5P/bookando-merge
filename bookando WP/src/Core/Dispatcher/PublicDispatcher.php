<?php

namespace Bookando\Core\Dispatcher;

/**
 * Dispatcher für öffentliche (nicht eingeloggte) Formulare, z.B. Buchungen.
 * Route: admin_post_nopriv_bookando_public / admin_post_bookando_public
 */
class PublicDispatcher
{
    public static function register(): void
    {
        // Für nicht eingeloggte User
        add_action('admin_post_nopriv_bookando_public', [self::class, 'handle']);
        // Für eingeloggte User (z.B. Double-Opt-In)
        add_action('admin_post_bookando_public', [self::class, 'handle']);
    }

    public static function handle()
    {
        // Beispiel: Buchungs-Logik
        // (Sanitizing, Security, etc. implementieren!)
        error_log('[Bookando] PublicDispatcher: Anfrage erhalten');

        // Platz für Custom-Form-Handler, z.B. $_POST['action'], etc.
        // ...

        // Redirect oder Response
        wp_redirect(home_url());
        exit;
    }
}
