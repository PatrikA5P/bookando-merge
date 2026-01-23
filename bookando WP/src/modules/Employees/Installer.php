<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees;

use Bookando\Core\Tenant\TenantManager;

class Installer
{
    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tableName = $wpdb->prefix . 'bookando_employees';
        $charset   = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(191) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) {$charset};";

        dbDelta($sql);

        // Create dummy employees if none exist
        self::createDummyEmployees();
    }

    /**
     * Create dummy employees for testing and demonstration
     */
    private static function createDummyEmployees(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_users';
        $tenantId = TenantManager::currentTenantId();

        // Check if employees already exist for this tenant
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE tenant_id = %d AND JSON_CONTAINS(roles, %s)",
            $tenantId,
            '"employee"'
        ));

        // Only create dummy employees if none exist
        if ((int)$count > 0) {
            return;
        }

        $dummyEmployees = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Müller',
                'email' => 'sarah.mueller@fahrschule.ch',
                'phone' => '+41 79 234 56 78',
                'address' => 'Bahnhofstrasse 45',
                'zip' => '8001',
                'city' => 'Zürich',
                'country' => 'CH',
                'gender' => 'female',
                'birthdate' => '1985-03-20',
                'status' => 'active',
                'badge' => 'FL-001',
                'position' => 'Fahrlehrer',
                'notes' => 'Spezialisiert auf Motorrad-Ausbildung'
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Schmid',
                'email' => 'thomas.schmid@fahrschule.ch',
                'phone' => '+41 79 345 67 89',
                'address' => 'Seestrasse 123',
                'zip' => '8802',
                'city' => 'Kilchberg',
                'country' => 'CH',
                'gender' => 'male',
                'birthdate' => '1978-11-12',
                'status' => 'active',
                'badge' => 'FL-002',
                'position' => 'Fahrlehrer',
                'notes' => 'VKU-Kursleiter'
            ],
            [
                'first_name' => 'Anna',
                'last_name' => 'Weber',
                'email' => 'anna.weber@fahrschule.ch',
                'phone' => '+41 79 456 78 90',
                'address' => 'Bergstrasse 78',
                'zip' => '8032',
                'city' => 'Zürich',
                'country' => 'CH',
                'gender' => 'female',
                'birthdate' => '1992-07-08',
                'status' => 'active',
                'badge' => 'FL-003',
                'position' => 'Fahrlehrerin',
                'notes' => 'Zweisprachig DE/FR'
            ],
            [
                'first_name' => 'Marco',
                'last_name' => 'Rossi',
                'email' => 'marco.rossi@fahrschule.ch',
                'phone' => '+41 79 567 89 01',
                'address' => 'Via Tessin 34',
                'zip' => '6900',
                'city' => 'Lugano',
                'country' => 'CH',
                'gender' => 'male',
                'birthdate' => '1988-02-25',
                'status' => 'active',
                'badge' => 'FL-004',
                'position' => 'Fahrlehrer',
                'notes' => 'Nothelferkurs-Instruktor'
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Meier',
                'email' => 'lisa.meier@fahrschule.ch',
                'phone' => '+41 79 678 90 12',
                'address' => 'Hauptstrasse 56',
                'zip' => '3000',
                'city' => 'Bern',
                'country' => 'CH',
                'gender' => 'female',
                'birthdate' => '1995-09-14',
                'status' => 'active',
                'badge' => 'ADM-001',
                'position' => 'Administration',
                'notes' => 'Kundenbetreuung & Terminplanung'
            ]
        ];

        foreach ($dummyEmployees as $employee) {
            $wpdb->insert(
                $table,
                [
                    'tenant_id' => $tenantId,
                    'first_name' => $employee['first_name'],
                    'last_name' => $employee['last_name'],
                    'email' => $employee['email'],
                    'phone' => $employee['phone'],
                    'address' => $employee['address'],
                    'zip' => $employee['zip'],
                    'city' => $employee['city'],
                    'country' => $employee['country'],
                    'gender' => $employee['gender'],
                    'birthdate' => $employee['birthdate'],
                    'roles' => json_encode(['employee']),
                    'status' => $employee['status'],
                    'badge_id' => $employee['badge'],
                    'note' => $employee['notes'],
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
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
                    '%s', // roles (JSON)
                    '%s', // status
                    '%s', // badge_id
                    '%s', // note
                    '%s', // created_at
                    '%s'  // updated_at
                ]
            );
        }
    }
}
