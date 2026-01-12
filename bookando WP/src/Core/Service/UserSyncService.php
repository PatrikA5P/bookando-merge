<?php
namespace Bookando\Core\Service;

/**
 * WordPress user synchronization service.
 *
 * Automatically synchronizes WordPress users with Bookando's internal
 * user management system. Creates Bookando user records when WP users
 * log in or access the admin area.
 */
class UserSyncService
{
    /**
     * Registers WordPress hooks for user synchronization.
     *
     * Hooks into wp_login and admin_init to ensure Bookando user
     * records are created/updated when users authenticate.
     *
     * @return void
     */
    public static function register_hooks()
    {
        // Beim WP-Login synchronisieren
        add_action('wp_login', [static::class, 'sync_wp_user'], 10, 2);

        // Optional: Auch bei Admin-Init (z. B. für REST/SSO)
        add_action('admin_init', [static::class, 'sync_wp_user_from_admin']);
    }

    /**
     * Synchronizes a WordPress user to Bookando's user system.
     *
     * Called on wp_login hook. Creates or updates the corresponding
     * Bookando user record using bookando_get_or_create_bookando_user().
     *
     * @param string|null $user_login WordPress username (unused, from hook)
     * @param \WP_User|null $user_obj WordPress user object
     * @return void
     */
    public static function sync_wp_user($user_login = null, $user_obj = null)
    {
        if (!$user_obj instanceof \WP_User) {
            $user_obj = wp_get_current_user();
        }
        if (!$user_obj || !$user_obj->ID) return;
        if (!function_exists('bookando_get_or_create_bookando_user')) return;
        // Synchronisierung (legt an, falls noch nicht da)
        bookando_get_or_create_bookando_user();
    }

    /**
     * Synchronizes the current user during admin initialization.
     *
     * Called on admin_init hook to ensure user sync happens for
     * admin access, REST API calls, and SSO scenarios.
     *
     * @return void
     */
    public static function sync_wp_user_from_admin()
    {
        if (!is_user_logged_in()) return;
        static::sync_wp_user();
    }
}
