<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

use Bookando\Core\Base\BaseModule;

/**
 * DesignFrontend Modul
 *
 * Ermöglicht die Anzeige von Angeboten, Kursen und Portalen auf der Website
 * via Shortcodes (WordPress) oder direkten Links (SaaS/Cloud).
 *
 * Features:
 * - Angebote nach Kategorie, Tag, Namen, ID anzeigen
 * - Kundenportal mit Authentifizierung
 * - Mitarbeiterportal mit Authentifizierung
 * - Buchungssystem Integration
 * - Auth: Email, Google, Apple ID
 */
class Module extends BaseModule
{
    protected static string $slug = 'design-frontend';
    protected static string $name = 'Design Frontend';
    protected static string $version = '1.0.0';
    protected static string $license_required = 'professional'; // Requires professional plan

    public static function init(): void
    {
        // Register shortcodes (Amelia-inspired flexible system)
        add_shortcode('bookando_booking', [ShortcodeHandler::class, 'renderBooking']);     // Step-by-step wizard
        add_shortcode('bookando_catalog', [ShortcodeHandler::class, 'renderCatalog']);     // Catalog view
        add_shortcode('bookando_list', [ShortcodeHandler::class, 'renderList']);           // List view
        add_shortcode('bookando_calendar', [ShortcodeHandler::class, 'renderCalendar']);   // Calendar view
        add_shortcode('bookando_customer_portal', [ShortcodeHandler::class, 'renderCustomerPortal']);
        add_shortcode('bookando_employee_portal', [ShortcodeHandler::class, 'renderEmployeePortal']);

        // Legacy shortcode (backward compatibility)
        add_shortcode('bookando_offers', [ShortcodeHandler::class, 'renderOffers']);

        // Register REST API
        add_action('rest_api_init', [Api\Api::class, 'registerRoutes']);

        // Initialize Admin
        if (is_admin()) {
            Admin\Admin::init();
        }

        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendScripts']);

        // Register rewrite rules for SaaS links
        add_action('init', [self::class, 'registerRewriteRules']);
    }

    public static function install(): void
    {
        Installer::install();
    }

    public static function uninstall(): void
    {
        Installer::uninstall();
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public static function enqueueFrontendScripts(): void
    {
        // Only load on pages with shortcodes or SaaS routes
        if (!self::shouldLoadFrontendAssets()) {
            return;
        }

        $asset_file = __DIR__ . '/assets/vue/dist/assets/index.asset.php';
        if (file_exists($asset_file)) {
            $asset = require $asset_file;

            wp_enqueue_script(
                'bookando-frontend',
                plugins_url('assets/vue/dist/assets/index.js', __FILE__),
                $asset['dependencies'] ?? [],
                $asset['version'] ?? self::$version,
                true
            );

            wp_enqueue_style(
                'bookando-frontend',
                plugins_url('assets/vue/dist/assets/index.css', __FILE__),
                [],
                $asset['version'] ?? self::$version
            );

            // Localize script with data
            wp_localize_script('bookando-frontend', 'bookandoFrontend', [
                'apiUrl' => rest_url('bookando/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'locale' => get_locale(),
                'isLoggedIn' => is_user_logged_in(),
                'currentUser' => is_user_logged_in() ? wp_get_current_user()->ID : null,
            ]);
        }
    }

    /**
     * Check if frontend assets should be loaded
     */
    protected static function shouldLoadFrontendAssets(): bool
    {
        global $post;

        // Check for shortcodes in content
        $shortcodes = [
            'bookando_booking',
            'bookando_catalog',
            'bookando_list',
            'bookando_calendar',
            'bookando_customer_portal',
            'bookando_employee_portal',
            'bookando_offers', // Legacy
        ];

        foreach ($shortcodes as $shortcode) {
            if ($post && has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        // Check for SaaS routes
        if (self::isSaaSRoute()) {
            return true;
        }

        return false;
    }

    /**
     * Check if current request is a SaaS route
     */
    protected static function isSaaSRoute(): bool
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return (
            strpos($request_uri, '/bookando/portal/customer') !== false ||
            strpos($request_uri, '/bookando/portal/employee') !== false ||
            strpos($request_uri, '/bookando/offers') !== false ||
            strpos($request_uri, '/bookando/booking') !== false
        );
    }

    /**
     * Register rewrite rules for SaaS links
     */
    public static function registerRewriteRules(): void
    {
        // Customer Portal: /bookando/portal/customer
        add_rewrite_rule(
            '^bookando/portal/customer/?$',
            'index.php?bookando_portal=customer',
            'top'
        );

        // Employee Portal: /bookando/portal/employee
        add_rewrite_rule(
            '^bookando/portal/employee/?$',
            'index.php?bookando_portal=employee',
            'top'
        );

        // Offers: /bookando/offers/{category}/{tag}
        add_rewrite_rule(
            '^bookando/offers/([^/]+)/([^/]+)/?$',
            'index.php?bookando_offers=1&category=$matches[1]&tag=$matches[2]',
            'top'
        );

        // Offers: /bookando/offers/{id}
        add_rewrite_rule(
            '^bookando/offers/([^/]+)/?$',
            'index.php?bookando_offers=1&offer_id=$matches[1]',
            'top'
        );

        // Booking: /bookando/booking/{offer_id}
        add_rewrite_rule(
            '^bookando/booking/([^/]+)/?$',
            'index.php?bookando_booking=1&offer_id=$matches[1]',
            'top'
        );

        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'bookando_portal';
            $vars[] = 'bookando_offers';
            $vars[] = 'bookando_booking';
            $vars[] = 'category';
            $vars[] = 'tag';
            $vars[] = 'offer_id';
            return $vars;
        });

        // Template redirect
        add_action('template_redirect', [self::class, 'handleSaaSRoutes']);
    }

    /**
     * Handle SaaS route requests
     */
    public static function handleSaaSRoutes(): void
    {
        $portal = get_query_var('bookando_portal');
        $offers = get_query_var('bookando_offers');
        $booking = get_query_var('bookando_booking');

        if ($portal === 'customer') {
            self::renderCustomerPortalPage();
            exit;
        }

        if ($portal === 'employee') {
            self::renderEmployeePortalPage();
            exit;
        }

        if ($offers) {
            self::renderOffersPage();
            exit;
        }

        if ($booking) {
            self::renderBookingPage();
            exit;
        }
    }

    /**
     * Render Customer Portal page
     */
    protected static function renderCustomerPortalPage(): void
    {
        self::renderFrontendPage('customer-portal', 'Kundenportal');
    }

    /**
     * Render Employee Portal page
     */
    protected static function renderEmployeePortalPage(): void
    {
        self::renderFrontendPage('employee-portal', 'Mitarbeiterportal');
    }

    /**
     * Render Offers page
     */
    protected static function renderOffersPage(): void
    {
        self::renderFrontendPage('offers', 'Angebote');
    }

    /**
     * Render Booking page
     */
    protected static function renderBookingPage(): void
    {
        self::renderFrontendPage('booking', 'Buchen');
    }

    /**
     * Render a frontend page
     */
    protected static function renderFrontendPage(string $type, string $title): void
    {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> | <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="bookando-frontend bookando-<?php echo esc_attr($type); ?>">
            <div id="bookando-app" data-type="<?php echo esc_attr($type); ?>"></div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}
