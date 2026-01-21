<?php
/**
 * Installer für Modul "customers"
 */
namespace Bookando\Modules\Customers;

use Bookando\Core\Tenant\TenantManager;

class Installer
{
    public static function install(): void
    {
        // Legacy-Tabellenanlage entfernt – das Modul verwendet die Core-Tabelle
        // `bookando_users` für die Kundendatenhaltung.

        // Create dummy customers if none exist
        self::createDummyCustomers();
    }

    /**
     * Create dummy customers for testing and demonstration
     */
    private static function createDummyCustomers(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_users';
        $tenantId = TenantManager::currentTenantId();

        // Check if customers already exist for this tenant
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE tenant_id = %d AND JSON_CONTAINS(roles, %s)",
            $tenantId,
            '"customer"'
        ));

        // Only create dummy customers if none exist
        if ((int)$count > 0) {
            return;
        }

        $dummyCustomers = [
            [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max.mustermann@example.com',
                'phone' => '+41 79 123 45 67',
                'address' => 'Musterstrasse 12',
                'zip' => '8000',
                'city' => 'Zürich',
                'country' => 'CH',
                'gender' => 'male',
                'birthdate' => '1990-05-15',
                'status' => 'active',
            ],
            [
                'first_name' => 'Anna',
                'last_name' => 'Schmidt',
                'email' => 'anna.schmidt@example.com',
                'phone' => '+41 79 234 56 78',
                'address' => 'Bahnhofstrasse 45',
                'zip' => '3000',
                'city' => 'Bern',
                'country' => 'CH',
                'gender' => 'female',
                'birthdate' => '1992-08-22',
                'status' => 'active',
            ],
            [
                'first_name' => 'Peter',
                'last_name' => 'Meier',
                'email' => 'peter.meier@example.com',
                'phone' => '+41 79 345 67 89',
                'address' => 'Hauptstrasse 78',
                'zip' => '4000',
                'city' => 'Basel',
                'country' => 'CH',
                'gender' => 'male',
                'birthdate' => '1988-03-10',
                'status' => 'active',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Weber',
                'email' => 'sarah.weber@example.com',
                'phone' => '+41 79 456 78 90',
                'address' => 'Seestrasse 23',
                'zip' => '6000',
                'city' => 'Luzern',
                'country' => 'CH',
                'gender' => 'female',
                'birthdate' => '1995-11-30',
                'status' => 'active',
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Müller',
                'email' => 'thomas.mueller@example.com',
                'phone' => '+41 79 567 89 01',
                'address' => 'Gartenweg 5',
                'zip' => '9000',
                'city' => 'St. Gallen',
                'country' => 'CH',
                'gender' => 'male',
                'birthdate' => '1987-07-18',
                'status' => 'active',
            ],
        ];

        foreach ($dummyCustomers as $customer) {
            $wpdb->insert(
                $table,
                [
                    'tenant_id' => $tenantId,
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'email' => $customer['email'],
                    'phone' => $customer['phone'],
                    'address' => $customer['address'],
                    'zip' => $customer['zip'],
                    'city' => $customer['city'],
                    'country' => $customer['country'],
                    'gender' => $customer['gender'],
                    'birthdate' => $customer['birthdate'],
                    'status' => $customer['status'],
                    'roles' => wp_json_encode(['customer']),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ],
                [
                    '%d', // tenant_id
                    '%s', // first_name
                    '%s', // last_name
                    '%s', // email
                    '%s', // phone
                    '%s', // address
                    '%s', // zip
                    '%s', // city
                    '%s', // country
                    '%s', // gender
                    '%s', // birthdate
                    '%s', // status
                    '%s', // roles
                    '%s', // created_at
                    '%s', // updated_at
                ]
            );
        }
    }
}
