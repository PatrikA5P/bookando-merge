<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Enhanced Shortcode Handler (Amelia-inspired)
 *
 * Principles:
 * - ID-based filters (not names)
 * - Comma-separated multiple values
 * - Tags in curly braces {tag1,tag2}
 * - Combinable filters
 * - Popup trigger support
 * - URL parameter pre-selection
 */
class ShortcodeHandler
{
    /**
     * Step-by-Step Booking
     *
     * Usage:
     * [bookando_booking]
     * [bookando_booking category=1,2 employee=5]
     * [bookando_booking offer=123 show=details]
     * [bookando_booking trigger="#book-now" trigger_type="id"]
     * [bookando_booking in_dialog=1]
     *
     * Filters:
     * - offer: Specific offer ID(s) - comma-separated
     * - category: Category ID(s) - comma-separated
     * - tag: Tag IDs in curly braces {1,2,3}
     * - employee: Employee/Instructor ID(s) - comma-separated
     * - location: Location ID(s) - comma-separated
     * - package: Package ID(s) - comma-separated
     *
     * Options:
     * - show: What to display (courses|packages|appointments|all)
     * - layout: Display layout (wizard|compact|inline)
     * - theme: Theme ID
     * - in_dialog: Show in popup (0|1)
     * - trigger: Element selector for popup trigger
     * - trigger_type: Type of selector (id|class)
     */
    public static function renderBooking($atts = []): string
    {
        $atts = shortcode_atts([
            // Filters (ID-based)
            'offer' => '',           // Offer ID(s): 123 or 1,2,3
            'category' => '',        // Category ID(s): 1 or 1,2,3
            'tag' => '',             // Tag IDs: {1,2,3}
            'employee' => '',        // Employee ID(s): 5 or 1,2,3
            'location' => '',        // Location ID(s)
            'package' => '',         // Package ID(s)

            // Display options
            'show' => 'all',         // courses|packages|appointments|all
            'layout' => 'wizard',    // wizard|compact|inline
            'theme' => '',           // Theme ID

            // Popup/Dialog
            'in_dialog' => '0',      // Show in popup: 0|1
            'trigger' => '',         // Popup trigger selector: #id or .class
            'trigger_type' => 'id',  // id|class

            // Pre-selection
            'preselect' => '1',      // Allow URL params: 0|1
        ], $atts);

        return self::renderWidget('booking', $atts);
    }

    /**
     * Catalog View (like Amelia Catalog)
     *
     * Usage:
     * [bookando_catalog]
     * [bookando_catalog category=1,2 layout=grid columns=3]
     * [bookando_catalog tag={beginner,advanced} featured=1]
     * [bookando_catalog show=courses employee=5]
     *
     * Filters:
     * - category: Category ID(s)
     * - tag: Tag IDs in curly braces
     * - employee: Filter by instructor
     * - location: Filter by location
     * - featured: Only featured (0|1)
     *
     * Display:
     * - layout: grid|list|masonry
     * - columns: 2|3|4|5
     * - show: courses|packages|all
     * - limit: Max items to show
     * - sort: price_asc|price_desc|popular|newest
     */
    public static function renderCatalog($atts = []): string
    {
        $atts = shortcode_atts([
            // Filters
            'category' => '',
            'tag' => '',
            'employee' => '',
            'location' => '',
            'featured' => '0',

            // Display
            'layout' => 'grid',
            'columns' => '3',
            'show' => 'all',
            'limit' => '12',
            'sort' => 'newest',
            'theme' => '',

            // Pagination
            'pagination' => '1',
            'per_page' => '12',
        ], $atts);

        return self::renderWidget('catalog', $atts);
    }

    /**
     * List View (simple vertical list)
     *
     * Usage:
     * [bookando_list category=1 limit=5]
     * [bookando_list employee=2 show=courses]
     */
    public static function renderList($atts = []): string
    {
        $atts = shortcode_atts([
            'category' => '',
            'tag' => '',
            'employee' => '',
            'location' => '',
            'show' => 'all',
            'limit' => '10',
            'theme' => '',
        ], $atts);

        return self::renderWidget('list', $atts);
    }

    /**
     * Calendar View
     *
     * Usage:
     * [bookando_calendar]
     * [bookando_calendar employee=5 category=1]
     * [bookando_calendar view=month] (month|week|day)
     */
    public static function renderCalendar($atts = []): string
    {
        $atts = shortcode_atts([
            'category' => '',
            'employee' => '',
            'location' => '',
            'view' => 'month',      // month|week|day
            'theme' => '',
        ], $atts);

        return self::renderWidget('calendar', $atts);
    }

    /**
     * Customer Portal
     *
     * Usage:
     * [bookando_customer_portal]
     * [bookando_customer_portal appointments=1 events=0]
     * [bookando_customer_portal theme=1]
     *
     * Required:
     * - appointments=1 OR events=1 (at least one)
     */
    public static function renderCustomerPortal($atts = []): string
    {
        $atts = shortcode_atts([
            'appointments' => '1',   // Show appointments tab (required)
            'events' => '1',         // Show events tab
            'invoices' => '1',       // Show invoices tab
            'progress' => '1',       // Show progress tab
            'theme' => '',
            'redirect_after_login' => '',
        ], $atts);

        return self::renderWidget('customer_portal', $atts);
    }

    /**
     * Employee Portal
     *
     * Usage:
     * [bookando_employee_portal]
     * [bookando_employee_portal profile_hidden=1]
     * [bookando_employee_portal theme=1]
     */
    public static function renderEmployeePortal($atts = []): string
    {
        $atts = shortcode_atts([
            'schedule' => '1',       // Show schedule
            'students' => '1',       // Show students
            'profile_hidden' => '0', // Hide profile settings
            'theme' => '',
            'redirect_after_login' => '',
        ], $atts);

        return self::renderWidget('employee_portal', $atts);
    }

    /**
     * Unified widget renderer
     */
    protected static function renderWidget(string $type, array $atts): string
    {
        // Ensure scripts are loaded
        Module::enqueueFrontendScripts();

        // Parse tags from curly braces {1,2,3} to array
        if (!empty($atts['tag']) && preg_match('/\{([^}]+)\}/', $atts['tag'], $matches)) {
            $atts['tag'] = explode(',', $matches[1]);
        } else {
            $atts['tag'] = !empty($atts['tag']) ? explode(',', $atts['tag']) : [];
        }

        // Parse comma-separated IDs
        foreach (['offer', 'category', 'employee', 'location', 'package'] as $key) {
            if (!empty($atts[$key])) {
                $atts[$key] = array_map('intval', explode(',', $atts[$key]));
            } else {
                $atts[$key] = [];
            }
        }

        // Convert boolean strings
        foreach (['in_dialog', 'featured', 'pagination', 'preselect', 'appointments', 'events', 'invoices', 'progress', 'schedule', 'students', 'profile_hidden'] as $key) {
            if (isset($atts[$key])) {
                $atts[$key] = in_array($atts[$key], ['1', 'true', 'yes'], true);
            }
        }

        // Theme ID
        if (!empty($atts['theme'])) {
            $atts['theme'] = (int)$atts['theme'];
        }

        $config = [
            'type' => $type,
            'filters' => [
                'offer' => $atts['offer'] ?? [],
                'category' => $atts['category'] ?? [],
                'tag' => $atts['tag'] ?? [],
                'employee' => $atts['employee'] ?? [],
                'location' => $atts['location'] ?? [],
                'package' => $atts['package'] ?? [],
                'featured' => $atts['featured'] ?? false,
            ],
            'display' => [
                'layout' => $atts['layout'] ?? 'grid',
                'columns' => (int)($atts['columns'] ?? 3),
                'show' => $atts['show'] ?? 'all',
                'limit' => (int)($atts['limit'] ?? 12),
                'sort' => $atts['sort'] ?? 'newest',
                'view' => $atts['view'] ?? 'month',
            ],
            'options' => [
                'theme' => $atts['theme'] ?? null,
                'in_dialog' => $atts['in_dialog'] ?? false,
                'trigger' => $atts['trigger'] ?? '',
                'trigger_type' => $atts['trigger_type'] ?? 'id',
                'preselect' => $atts['preselect'] ?? true,
                'pagination' => $atts['pagination'] ?? true,
                'per_page' => (int)($atts['per_page'] ?? 12),
                'redirect_after_login' => $atts['redirect_after_login'] ?? '',
            ],
            'portal' => [
                'appointments' => $atts['appointments'] ?? true,
                'events' => $atts['events'] ?? true,
                'invoices' => $atts['invoices'] ?? true,
                'progress' => $atts['progress'] ?? true,
                'schedule' => $atts['schedule'] ?? true,
                'students' => $atts['students'] ?? true,
                'profile_hidden' => $atts['profile_hidden'] ?? false,
            ],
        ];

        // Add popup trigger attributes if specified
        $triggerAttr = '';
        if (!empty($atts['trigger'])) {
            $triggerAttr = sprintf(
                'data-trigger="%s" data-trigger-type="%s"',
                esc_attr($atts['trigger']),
                esc_attr($atts['trigger_type'])
            );
        }

        return sprintf(
            '<div class="bookando-widget bookando-%s" data-config=\'%s\' %s></div>',
            esc_attr($type),
            esc_attr(wp_json_encode($config)),
            $triggerAttr
        );
    }

    /**
     * Legacy shortcodes (backward compatibility)
     */
    public static function renderOffers($atts = []): string
    {
        // Convert legacy to catalog
        return self::renderCatalog($atts);
    }
}
