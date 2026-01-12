<?php

namespace Bookando\CLI;

use Bookando\Core\Service\ActivityLogger;
use WP_CLI;
use WP_CLI_Command;

class SeedDevLicenseCommand extends WP_CLI_Command
{
    /**
     * Seed development license data for local environments.
     *
     * ## EXAMPLES
     *
     *     wp bookando seed-dev-license
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        if (wp_get_environment_type() !== 'development') {
            WP_CLI::error(__('Der Befehl ist nur in einer Entwicklungsumgebung verfügbar.', 'bookando'));
        }

        if (!current_user_can('manage_options')) {
            WP_CLI::error(__('Dir fehlt die Berechtigung "manage_options", um Lizenzdaten zu setzen.', 'bookando'));
        }

        $existing = get_option('bookando_license_data');
        if (!empty($existing)) {
            WP_CLI::warning(__('Es existieren bereits Lizenzdaten. Überspringe Debug-Seeding.', 'bookando'));
            return;
        }

        $data = $this->buildLicensePayload();
        update_option('bookando_license_data', $data);

        ActivityLogger::info('core.license', 'Dev dummy license seeded via CLI');
        WP_CLI::success(__('Entwicklungs-Lizenzdaten wurden gesetzt.', 'bookando'));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLicensePayload(): array
    {
        return [
            'key'         => 'dev-local-key',
            'plan'        => 'enterprise',
            'modules'     => [
                'customers', 'appointments', 'events', 'payments', 'services',
                'employees', 'resources', 'locations', 'discounts', 'notifications',
                'analytics', 'custom_fields', 'invoices', 'packages', 'reports',
                'education_cards', 'tests',
            ],
            'features'    => [
                'export_csv', 'calendar_sync', 'rest_api_read', 'rest_api_write',
                'export_pdf', 'white_label', 'multi_tenant', 'feedback', 'webhooks',
                'online_payment', 'student_offline', 'grade_export', 'progress_tracking',
                'notifications_whatsapp',
            ],
            'verified_at' => current_time('mysql'),
        ];
    }
}
