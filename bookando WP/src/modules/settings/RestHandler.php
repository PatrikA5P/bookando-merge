<?php

declare(strict_types=1);

namespace Bookando\Modules\settings;

use Bookando\Core\Api\Response;
use Bookando\Core\Dispatcher\RestModuleGuard;
use Bookando\Core\Plugin;
use WP_REST_Request;
use WP_REST_Server;
use function __;
use function current_time;
use function esc_url_raw;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function wp_json_encode;

/**
 * REST API handler for system settings operations.
 *
 * Manages company settings, general settings, role permissions, and feature flags.
 * All settings are tenant-aware and properly sanitized before storage.
 */
class RestHandler
{
    /**
     * Handles company settings (GET/POST).
     *
     * GET: Retrieves current company settings including name, address, contact info, and logo.
     * POST: Updates company settings with sanitized input.
     *
     * Endpoint: /wp-json/bookando/v1/settings/company
     *
     * @param WP_REST_Request $request The REST request object
     * @return \WP_REST_Response Response with company settings or update confirmation
     */
    public static function company(WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';

        if ($request->get_method() === 'GET') {
            // Use prepare() for consistency, even though IS NULL is safe
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_company_settings WHERE tenant_id IS NULL LIMIT 1"
            ));
            $settings = $row ? [
                'name'      => $row->name ?: '',
                'address'   => $row->address ?: '',
                'phone'     => $row->phone ?: '',
                'email'     => $row->email ?: '',
                'website'   => $row->website ?: '',
                'logo_url'  => $row->logo_url ?: ''
            ] : [
                'name' => '', 'address' => '', 'phone' => '', 'email' => '', 'website' => '', 'logo_url' => ''
            ];
            return Response::ok($settings);
        }

        if ($request->get_method() === 'POST') {
            $input  = (array) $request->get_json_params();
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bookando_company_settings WHERE tenant_id IS NULL"
            ));
            $data = [
                'name'       => sanitize_text_field($input['name'] ?? ''),
                'address'    => sanitize_text_field($input['address'] ?? ''),
                'phone'      => sanitize_text_field($input['phone'] ?? ''),
                'email'      => sanitize_email($input['email'] ?? ''),
                'website'    => esc_url_raw($input['website'] ?? ''),
                'logo_url'   => esc_url_raw($input['logo_url'] ?? ''),
                'updated_at' => current_time('mysql'),
            ];
            if ($exists) {
                $wpdb->update(
                    "{$wpdb->prefix}bookando_company_settings",
                    $data,
                    ['id' => (int) $exists],
                    null,
                    ['%d'] // Format for WHERE clause
                );
            } else {
                $data['created_at'] = current_time('mysql');
                $wpdb->insert("{$wpdb->prefix}bookando_company_settings", $data);
            }
            return Response::ok(['saved' => $data]);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    /**
     * Handles general system settings (GET/POST).
     *
     * GET: Retrieves general plugin configuration (stored as JSON).
     * POST: Updates general settings and flushes cache.
     *
     * General settings include global plugin options, defaults, and preferences
     * that affect the entire system.
     *
     * Endpoint: /wp-json/bookando/v1/settings/general
     *
     * @param WP_REST_Request $request The REST request object
     * @return \WP_REST_Response Response with general settings or update confirmation
     */
    public static function general(WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';

        if ($request->get_method() === 'GET') {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT value FROM {$p}settings WHERE tenant_id IS NULL AND settings_key = %s",
                'general'
            ));
            $settings = $row ? json_decode($row->value, true) : [];
            return Response::ok($settings ?: []);
        }

        if ($request->get_method() === 'POST') {
            $input  = (array) $request->get_json_params();
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$p}settings WHERE tenant_id IS NULL AND settings_key = %s",
                'general'
            ));
            $payload = wp_json_encode($input);
            if ($exists) {
                $wpdb->update("{$p}settings", [
                    'value'      => $payload,
                    'updated_at' => current_time('mysql'),
                ], ['id' => $exists]);
            } else {
                $wpdb->insert("{$p}settings", [
                    'tenant_id'    => null,
                    'settings_key' => 'general',
                    'value'        => $payload,
                    'created_at'   => current_time('mysql'),
                    'updated_at'   => current_time('mysql'),
                ]);
            }
            Plugin::flush_general_settings_cache();
            return Response::ok(['saved' => $input]);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    /**
     * Handles role-specific settings (GET/POST).
     *
     * GET: Retrieves settings for a specific user role.
     * POST: Updates role-specific settings (permissions, capabilities, UI preferences).
     *
     * Role settings control what features and data are accessible to users
     * with different roles (e.g., admin, manager, employee, customer).
     *
     * Endpoint: /wp-json/bookando/v1/settings/roles/{role_slug}
     *
     * @param WP_REST_Request $request The REST request object with role_slug parameter
     * @return \WP_REST_Response Response with role settings or update confirmation
     */
    public static function roles(WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';

        $role_slug = sanitize_key((string) $request->get_param('role_slug'));

        if (empty($role_slug)) {
            return Response::error([
                'code'    => 'missing_role_slug',
                'message' => __('Die Rollen-ID fehlt.', 'bookando'),
            ], 400);
        }

        if ($request->get_method() === 'GET') {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT settings FROM {$p}role_settings WHERE tenant_id IS NULL AND role_slug = %s",
                $role_slug
            ));
            $settings = $row ? json_decode($row->settings, true) : [];
            return Response::ok($settings ?: []);
        }

        if ($request->get_method() === 'POST') {
            $input  = (array) $request->get_json_params();
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$p}role_settings WHERE tenant_id IS NULL AND role_slug = %s",
                $role_slug
            ));
            $payload = wp_json_encode($input);
            if ($exists) {
                $wpdb->update("{$p}role_settings", [
                    'settings'   => $payload,
                    'updated_at' => current_time('mysql'),
                ], ['id' => $exists]);
            } else {
                $wpdb->insert("{$p}role_settings", [
                    'tenant_id'  => null,
                    'role_slug'  => $role_slug,
                    'settings'   => $payload,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]);
            }
            return Response::ok(['saved' => $input]);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    /**
     * Handles feature flag settings (GET/POST).
     *
     * GET: Retrieves configuration for a specific feature.
     * POST: Updates feature-specific settings (enable/disable, options, preferences).
     *
     * Feature settings allow granular control over plugin functionality,
     * enabling/disabling specific features or configuring their behavior
     * (e.g., payment gateways, integrations, experimental features).
     *
     * Endpoint: /wp-json/bookando/v1/settings/feature/{feature_key}
     *
     * @param WP_REST_Request $request The REST request object with feature_key parameter
     * @return \WP_REST_Response Response with feature settings or update confirmation
     */
    public static function feature(WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';
        $subkey = sanitize_key((string) $request->get_param('feature_key'));

        if (empty($subkey)) {
            return Response::error([
                'code'    => 'missing_feature_key',
                'message' => __('Der Feature-Schlüssel fehlt.', 'bookando'),
            ], 400);
        }

        if ($request->get_method() === 'GET') {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT value FROM {$p}feature_settings WHERE tenant_id IS NULL AND feature_key = %s",
                $subkey
            ));
            $settings = $row ? json_decode($row->value, true) : [];
            return Response::ok($settings ?: []);
        }

        if ($request->get_method() === 'POST') {
            $input  = (array) $request->get_json_params();
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$p}feature_settings WHERE tenant_id IS NULL AND feature_key = %s",
                $subkey
            ));
            $payload = wp_json_encode($input);
            if ($exists) {
                $wpdb->update("{$p}feature_settings", [
                    'value'      => $payload,
                    'updated_at' => current_time('mysql'),
                ], ['id' => $exists]);
            } else {
                $wpdb->insert("{$p}feature_settings", [
                    'tenant_id'   => null,
                    'feature_key' => $subkey,
                    'value'       => $payload,
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => current_time('mysql'),
                ]);
            }
            return Response::ok(['saved' => $input]);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

}
