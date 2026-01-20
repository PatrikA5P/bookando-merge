<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Handles all Bookando shortcodes for frontend display
 */
class ShortcodeHandler
{
    /**
     * Render offers shortcode
     *
     * Usage:
     * [bookando_offers]
     * [bookando_offers category="driving"]
     * [bookando_offers tag="beginner"]
     * [bookando_offers category="driving" tag="beginner"]
     * [bookando_offers ids="1,2,3"]
     * [bookando_offers layout="grid" columns="3"]
     */
    public static function renderOffers($atts = []): string
    {
        $atts = shortcode_atts([
            'category' => '',
            'tag' => '',
            'tags' => '',
            'ids' => '',
            'layout' => 'grid', // grid or list
            'columns' => '3',
            'featured' => '',
            'limit' => '12',
        ], $atts);

        // Ensure scripts are loaded
        Module::enqueueFrontendScripts();

        $config = [
            'type' => 'offers',
            'filters' => [
                'category' => $atts['category'],
                'tag' => $atts['tag'] ?: $atts['tags'],
                'ids' => $atts['ids'] ? explode(',', $atts['ids']) : [],
                'featured' => $atts['featured'] === 'true' || $atts['featured'] === '1',
            ],
            'display' => [
                'layout' => $atts['layout'],
                'columns' => (int)$atts['columns'],
                'limit' => (int)$atts['limit'],
            ],
        ];

        return sprintf(
            '<div class="bookando-widget bookando-offers" data-config=\'%s\'></div>',
            esc_attr(wp_json_encode($config))
        );
    }

    /**
     * Render customer portal shortcode
     *
     * Usage:
     * [bookando_customer_portal]
     * [bookando_customer_portal theme="light"]
     */
    public static function renderCustomerPortal($atts = []): string
    {
        $atts = shortcode_atts([
            'theme' => 'light',
            'redirect_after_login' => '',
        ], $atts);

        // Ensure scripts are loaded
        Module::enqueueFrontendScripts();

        $config = [
            'type' => 'customer_portal',
            'theme' => $atts['theme'],
            'redirectAfterLogin' => $atts['redirect_after_login'],
        ];

        return sprintf(
            '<div class="bookando-widget bookando-customer-portal" data-config=\'%s\'></div>',
            esc_attr(wp_json_encode($config))
        );
    }

    /**
     * Render employee portal shortcode
     *
     * Usage:
     * [bookando_employee_portal]
     * [bookando_employee_portal theme="dark"]
     */
    public static function renderEmployeePortal($atts = []): string
    {
        $atts = shortcode_atts([
            'theme' => 'light',
            'redirect_after_login' => '',
        ], $atts);

        // Ensure scripts are loaded
        Module::enqueueFrontendScripts();

        $config = [
            'type' => 'employee_portal',
            'theme' => $atts['theme'],
            'redirectAfterLogin' => $atts['redirect_after_login'],
        ];

        return sprintf(
            '<div class="bookando-widget bookando-employee-portal" data-config=\'%s\'></div>',
            esc_attr(wp_json_encode($config))
        );
    }

    /**
     * Render booking widget shortcode
     *
     * Usage:
     * [bookando_booking offer_id="123"]
     * [bookando_booking offer_id="123" theme="light"]
     */
    public static function renderBooking($atts = []): string
    {
        $atts = shortcode_atts([
            'offer_id' => '',
            'offer_type' => 'course', // course, appointment, package
            'theme' => 'light',
            'show_details' => 'true',
        ], $atts);

        // Ensure scripts are loaded
        Module::enqueueFrontendScripts();

        $config = [
            'type' => 'booking',
            'offerId' => $atts['offer_id'],
            'offerType' => $atts['offer_type'],
            'theme' => $atts['theme'],
            'showDetails' => $atts['show_details'] === 'true' || $atts['show_details'] === '1',
        ];

        return sprintf(
            '<div class="bookando-widget bookando-booking" data-config=\'%s\'></div>',
            esc_attr(wp_json_encode($config))
        );
    }
}
