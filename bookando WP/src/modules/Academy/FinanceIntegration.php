<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy;

use Bookando\Modules\Finance\StateRepository as FinanceStateRepository;

/**
 * Integration zwischen Academy und Finance Modulen.
 *
 * HINWEIS: Training Card Lektionen werden NICHT automatisch abgerechnet.
 * Die Abrechnung erfolgt ausschließlich über gebuchte Items (Appointments, Kurse).
 * Diese Klasse bietet Hilfsmethoden für manuelle Verknüpfungen und Statistiken.
 */
class FinanceIntegration
{

    /**
     * Erstellt eine Rechnung für ein ganzes Paket.
     *
     * @param array $package Das Paket
     * @param string $customerName Der Kundenname
     * @return string|null Die Rechnungsnummer bei Erfolg
     */
    public static function createInvoiceForPackage(array $package, string $customerName): ?string
    {
        if (!class_exists('Bookando\Modules\Finance\StateRepository')) {
            return null;
        }

        $price = (float)($package['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        try {
            $items = [];
            $packageItems = $package['items'] ?? [];

            // Erstelle Rechnungsposition für das Paket
            $description = $package['title'] ?? 'Ausbildungspaket';
            if (!empty($packageItems) && is_array($packageItems)) {
                $description .= ' (' . count($packageItems) . ' Leistungen)';
            }

            $invoiceData = [
                'customer' => $customerName,
                'date' => current_time('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'status' => 'open',
                'currency' => $package['currency'] ?? 'CHF',
                'auto_generated' => true,
                'items' => [
                    [
                        'description' => $description,
                        'type' => 'service',
                        'reference' => 'academy_package_' . ($package['id'] ?? ''),
                        'quantity' => 1,
                        'unit_price' => $price,
                        'tax_rate' => 0,
                        'account' => '3000',
                    ]
                ],
            ];

            $invoice = FinanceStateRepository::upsertInvoice($invoiceData);

            if (!empty($invoice['number'])) {
                error_log('[Bookando Academy] Created invoice ' . $invoice['number'] . ' for package ' . ($package['id'] ?? ''));
                return $invoice['number'];
            }
        } catch (\Exception $e) {
            error_log('[Bookando Academy] Failed to create package invoice: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Lädt die Finance-Einstellungen.
     *
     * @return array Die Einstellungen
     */
    protected static function getFinanceSettings(): array
    {
        if (!class_exists('Bookando\Modules\Finance\StateRepository')) {
            return [];
        }

        try {
            $state = FinanceStateRepository::getState();
            return $state['settings'] ?? [];
        } catch (\Exception $e) {
            error_log('[Bookando Academy] Failed to load finance settings: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Berechnet die Gesamteinnahmen aus Academy-Lektionen.
     *
     * @return float Die Gesamteinnahmen
     */
    public static function calculateTotalRevenue(): float
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_academy_training_lessons';

        $result = $wpdb->get_var(
            "SELECT SUM(price) FROM {$table} WHERE completed = 1 AND payment_status = 'paid'"
        );

        return (float)($result ?? 0);
    }

    /**
     * Berechnet die ausstehenden Zahlungen aus Academy-Lektionen.
     *
     * @return float Die ausstehenden Zahlungen
     */
    public static function calculateOutstandingPayments(): float
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_academy_training_lessons';

        $result = $wpdb->get_var(
            "SELECT SUM(price) FROM {$table} WHERE completed = 1 AND payment_status IN ('unpaid', 'partial')"
        );

        return (float)($result ?? 0);
    }

    /**
     * Lädt alle unbezahlten Academy-Lektionen.
     *
     * @return array Liste der unbezahlten Lektionen mit Details
     */
    public static function getUnpaidLessons(): array
    {
        global $wpdb;
        $lessonsTable = $wpdb->prefix . 'bookando_academy_training_lessons';
        $topicsTable = $wpdb->prefix . 'bookando_academy_training_topics';
        $cardsTable = $wpdb->prefix . 'bookando_academy_training_cards';

        $sql = "
            SELECT
                l.id,
                l.title AS lesson_title,
                l.price,
                l.completed_at,
                l.invoice_id,
                t.title AS topic_title,
                c.student,
                c.category
            FROM {$lessonsTable} l
            INNER JOIN {$topicsTable} t ON l.topic_id = t.id
            INNER JOIN {$cardsTable} c ON t.card_id = c.id
            WHERE l.completed = 1
            AND l.payment_status IN ('unpaid', 'partial')
            AND l.price > 0
            ORDER BY l.completed_at DESC
        ";

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }
}
