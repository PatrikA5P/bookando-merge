<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy;

use Bookando\Modules\Finance\StateRepository as FinanceStateRepository;

/**
 * Integration zwischen Academy und Finance Modulen.
 * Handhabt automatische Rechnungserstellung für abgeschlossene Lektionen.
 */
class FinanceIntegration
{
    /**
     * Erstellt automatisch eine Rechnung für eine abgeschlossene Lektion.
     *
     * @param array $lesson Die abgeschlossene Lektion mit allen Daten
     * @param array $trainingCard Die zugehörige Training Card
     * @return string|null Die Rechnungsnummer bei Erfolg, null bei Fehler
     */
    public static function createInvoiceForLesson(array $lesson, array $trainingCard): ?string
    {
        // Prüfe ob Finance Modul verfügbar ist
        if (!class_exists('Bookando\Modules\Finance\StateRepository')) {
            error_log('[Bookando Academy] Finance module not available for invoice creation');
            return null;
        }

        // Prüfe Auto-Invoice Einstellung
        $financeSettings = self::getFinanceSettings();
        if (empty($financeSettings['auto_invoice'])) {
            error_log('[Bookando Academy] Auto-invoice is disabled');
            return null;
        }

        // Prüfe ob Lektion einen Preis hat
        $price = (float)($lesson['price'] ?? 0);
        if ($price <= 0) {
            error_log('[Bookando Academy] Lesson has no price, skipping invoice creation');
            return null;
        }

        // Prüfe ob bereits eine Rechnung existiert
        if (!empty($lesson['invoice_id'])) {
            error_log('[Bookando Academy] Lesson already has an invoice: ' . $lesson['invoice_id']);
            return $lesson['invoice_id'];
        }

        // Bestimme Kunden-Namen
        $customer = $trainingCard['student'] ?? 'Unbekannter Kunde';

        // Erstelle Rechnung
        try {
            $invoiceData = [
                'customer' => $customer,
                'date' => current_time('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'status' => 'open',
                'currency' => 'CHF',
                'auto_generated' => true,
                'items' => [
                    [
                        'description' => $lesson['title'] ?? 'Fahrstunde',
                        'type' => 'service',
                        'reference' => 'academy_lesson_' . ($lesson['id'] ?? ''),
                        'quantity' => 1,
                        'unit_price' => $price,
                        'tax_rate' => 0, // Fahrschulunterricht ist oft steuerfrei
                        'account' => '3000', // Dienstleistungserlöse
                    ]
                ],
            ];

            $invoice = FinanceStateRepository::upsertInvoice($invoiceData);

            if (!empty($invoice['number'])) {
                error_log('[Bookando Academy] Created invoice ' . $invoice['number'] . ' for lesson ' . ($lesson['id'] ?? ''));
                return $invoice['number'];
            }
        } catch (\Exception $e) {
            error_log('[Bookando Academy] Failed to create invoice: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Aktualisiert den Zahlungsstatus einer Lektion basierend auf der Rechnung.
     *
     * @param string $invoiceNumber Die Rechnungsnummer
     * @return string Der Zahlungsstatus ('paid', 'unpaid', 'partial')
     */
    public static function getPaymentStatusForInvoice(string $invoiceNumber): string
    {
        if (!class_exists('Bookando\Modules\Finance\StateRepository')) {
            return 'unpaid';
        }

        try {
            $state = FinanceStateRepository::getState();
            $invoices = $state['invoices'] ?? [];

            foreach ($invoices as $invoice) {
                if ($invoice['number'] === $invoiceNumber) {
                    $status = $invoice['status'] ?? 'open';

                    switch ($status) {
                        case 'paid':
                            return 'paid';
                        case 'open':
                        case 'draft':
                            return 'unpaid';
                        case 'cancelled':
                            return 'unpaid';
                        default:
                            return 'unpaid';
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('[Bookando Academy] Failed to get payment status: ' . $e->getMessage());
        }

        return 'unpaid';
    }

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
