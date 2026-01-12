<?php

declare(strict_types=1);

namespace Bookando\Modules\finance;

use Bookando\Core\Util\Sanitizer;
use function __;
use function get_option;
use function update_option;
use function wp_generate_uuid4;
use function wp_parse_args;

class StateRepository
{
    private const OPTION_KEY = 'bookando_finance_state';

    private static ?array $stateCache = null;

    public static function getState(): array
    {
        if (self::$stateCache !== null) {
            return self::$stateCache;
        }

        $stored = get_option(self::OPTION_KEY, null);
        if (!is_array($stored) || $stored === []) {
            return self::seedDefaults();
        }

        return self::$stateCache = self::normalizeState($stored);
    }

    public static function saveState(array $state): void
    {
        $normalized = self::normalizeState($state);
        update_option(self::OPTION_KEY, $normalized, false);
        self::$stateCache = $normalized;
    }

    public static function upsertInvoice(array $payload, bool $isCreditNote = false): array
    {
        $state = self::getState();
        $listKey = $isCreditNote ? 'credit_notes' : 'invoices';
        $invoice = self::sanitizeInvoice($payload);
        if (empty($invoice['id'])) {
            $invoice['id'] = wp_generate_uuid4();
            $invoice['created_at'] = current_time('mysql');
        }
        if (empty($invoice['number'])) {
            $invoice['number'] = self::generateNumber($state[$listKey], $isCreditNote ? 'CN' : 'INV');
        }
        $invoice['updated_at'] = current_time('mysql');
        $invoice = self::recalculateTotals($invoice);

        $found = false;
        foreach ($state[$listKey] as $index => $existing) {
            if (!empty($existing['id']) && $existing['id'] === $invoice['id']) {
                $state[$listKey][$index] = array_merge($existing, $invoice);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $state[$listKey][] = $invoice;
        }

        self::saveState($state);
        return $invoice;
    }

    public static function deleteInvoice(string $id, bool $isCreditNote = false): bool
    {
        $state = self::getState();
        $listKey = $isCreditNote ? 'credit_notes' : 'invoices';
        $before = count($state[$listKey]);
        $state[$listKey] = array_values(array_filter($state[$listKey], static fn($entry) => ($entry['id'] ?? null) !== $id));
        self::saveState($state);
        return $before !== count($state[$listKey]);
    }

    public static function upsertDiscountCode(array $payload): array
    {
        $state = self::getState();
        $code = self::sanitizeDiscountCode($payload);
        if (empty($code['id'])) {
            $code['id'] = wp_generate_uuid4();
            $code['usage_count'] = 0;
            $code['created_at'] = current_time('mysql');
        }

        $code['updated_at'] = current_time('mysql');

        $found = false;
        foreach ($state['discount_codes'] as $index => $existing) {
            if (!empty($existing['id']) && $existing['id'] === $code['id']) {
                $state['discount_codes'][$index] = array_merge($existing, $code);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $state['discount_codes'][] = $code;
        }

        self::saveState($state);
        return $code;
    }

    public static function deleteDiscountCode(string $id): bool
    {
        $state = self::getState();
        $before = count($state['discount_codes']);
        $state['discount_codes'] = array_values(array_filter(
            $state['discount_codes'],
            static fn($entry) => ($entry['id'] ?? null) !== $id
        ));
        self::saveState($state);
        return $before !== count($state['discount_codes']);
    }

    public static function setSettings(array $settings): array
    {
        $state = self::getState();
        $state['settings'] = self::sanitizeSettings(array_merge($state['settings'], $settings));
        self::saveState($state);
        return $state['settings'];
    }

    public static function getLedgerExport(?string $from = null, ?string $to = null): array
    {
        $state = self::getState();
        $entries = [];
        $all = array_merge(
            array_map(static fn($invoice) => array_merge($invoice, ['type' => 'invoice']), $state['invoices']),
            array_map(static fn($note) => array_merge($note, ['type' => 'credit_note']), $state['credit_notes'])
        );
        foreach ($all as $doc) {
            $date = $doc['date'] ?? null;
            if ($from && $date && $date < $from) {
                continue;
            }
            if ($to && $date && $date > $to) {
                continue;
            }
            foreach ($doc['items'] as $item) {
                $entries[] = [
                    'document_id'   => $doc['id'],
                    'document_type' => $doc['type'],
                    'document_no'   => $doc['number'],
                    'date'          => $doc['date'],
                    'customer'      => $doc['customer'],
                    'account'       => $item['account'] ?? ($item['type'] === 'course' ? '3200' : '3000'),
                    'description'   => $item['description'],
                    'amount'        => (float) $item['total'],
                    'tax_rate'      => (float) $item['tax_rate'],
                    'currency'      => $doc['currency'] ?? 'CHF',
                ];
            }
        }

        return [
            'generated_at' => current_time('mysql'),
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'settings' => $state['settings'],
        ];
    }

    public static function seedDefaults(bool $force = false): array
    {
        $existing = get_option(self::OPTION_KEY, null);
        if (!$force && is_array($existing) && !empty($existing)) {
            $normalized = self::normalizeState($existing);
            return self::$stateCache = $normalized;
        }

        $defaults = self::buildDefaultState();
        $normalized = self::normalizeState($defaults);
        update_option(self::OPTION_KEY, $normalized, false);

        return self::$stateCache = $normalized;
    }

    public static function resetCache(): void
    {
        self::$stateCache = null;
    }

    private static function sanitizeInvoice(array $invoice): array
    {
        $invoice['id'] = isset($invoice['id']) ? Sanitizer::text((string) $invoice['id']) : '';
        $invoice['number'] = Sanitizer::text(is_scalar($invoice['number'] ?? null) ? (string) $invoice['number'] : '');
        $invoice['customer'] = Sanitizer::text(is_scalar($invoice['customer'] ?? null) ? (string) $invoice['customer'] : '');
        $invoice['date'] = Sanitizer::date($invoice['date'] ?? null) ?? current_time('Y-m-d');
        $invoice['due_date'] = Sanitizer::date($invoice['due_date'] ?? null) ?? $invoice['date'];
        $invoice['status'] = Sanitizer::key($invoice['status'] ?? 'open');
        $invoice['currency'] = Sanitizer::text(is_scalar($invoice['currency'] ?? null) ? (string) $invoice['currency'] : 'CHF');
        $invoice['auto_generated'] = !empty($invoice['auto_generated']);
        $invoice['items'] = array_values(array_map([self::class, 'sanitizeItem'], $invoice['items'] ?? []));
        $invoice['created_at'] = $invoice['created_at'] ?? current_time('mysql');
        $invoice['updated_at'] = $invoice['updated_at'] ?? current_time('mysql');
        return self::recalculateTotals($invoice);
    }

    private static function sanitizeItem(array $item): array
    {
        $quantity = max(0, (float) ($item['quantity'] ?? 1));
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $taxRate = (float) ($item['tax_rate'] ?? 0);
        $total = $quantity * $unitPrice;
        return [
            'description' => Sanitizer::text(is_scalar($item['description'] ?? null) ? (string) $item['description'] : ''),
            'type' => Sanitizer::key($item['type'] ?? 'service'),
            'reference' => Sanitizer::text(is_scalar($item['reference'] ?? null) ? (string) $item['reference'] : ''),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'total' => $total,
            'account' => Sanitizer::text(is_scalar($item['account'] ?? null) ? (string) $item['account'] : ''),
        ];
    }

    private static function sanitizeSettings(array $settings): array
    {
        return [
            'auto_invoice' => !empty($settings['auto_invoice']),
            'auto_send' => !empty($settings['auto_send']),
            'batch_mode' => in_array($settings['batch_mode'] ?? 'manual', ['manual', 'daily', 'weekly', 'monthly'], true)
                ? $settings['batch_mode']
                : 'manual',
        ];
    }

    private static function sanitizeLedger(array $ledger): array
    {
        $accounts = array_values(array_map(static function ($account) {
            return [
                'code' => Sanitizer::text(is_scalar($account['code'] ?? null) ? (string) $account['code'] : ''),
                'name' => Sanitizer::text(is_scalar($account['name'] ?? null) ? (string) $account['name'] : ''),
                'type' => Sanitizer::key($account['type'] ?? 'revenue'),
            ];
        }, $ledger['accounts'] ?? []));

        if (!$accounts) {
            $accounts = [
                ['code' => '3000', 'name' => __('Dienstleistungen', 'bookando'), 'type' => 'revenue'],
                ['code' => '3200', 'name' => __('Kurse & Ausbildung', 'bookando'), 'type' => 'revenue'],
                ['code' => '2000', 'name' => __('Umsatzsteuer', 'bookando'), 'type' => 'tax'],
            ];
        }

        return [
            'accounts' => $accounts,
            'exported_at' => $ledger['exported_at'] ?? null,
        ];
    }

    private static function recalculateTotals(array $invoice): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($invoice['items'] as $index => $item) {
            $item = self::sanitizeItem($item);
            $invoice['items'][$index] = $item;
            $subtotal += $item['total'];
            $taxTotal += $item['total'] * ($item['tax_rate'] / 100);
        }
        $invoice['subtotal'] = round($subtotal, 2);
        $invoice['tax_total'] = round($taxTotal, 2);
        $invoice['total'] = round($subtotal + $taxTotal, 2);
        return $invoice;
    }

    private static function sanitizeDiscountCode(array $code): array
    {
        $allowedTypes = ['percentage', 'fixed'];
        $type = Sanitizer::key($code['discount_type'] ?? 'percentage');
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'percentage';
        }

        $applies = array_map(static fn($value) => Sanitizer::text(is_scalar($value) ? (string) $value : ''), (array)($code['applies_to'] ?? []));
        $applies = array_values(array_filter(array_map('trim', $applies)));

        return [
            'id'            => isset($code['id']) ? Sanitizer::text((string) $code['id']) : '',
            'code'          => Sanitizer::text(is_scalar($code['code'] ?? null) ? (string) $code['code'] : ''),
            'description'   => Sanitizer::text(is_scalar($code['description'] ?? null) ? (string) $code['description'] : ''),
            'discount_type' => $type,
            'value'         => (float) ($code['value'] ?? 0),
            'valid_from'    => $code['valid_from'] ? Sanitizer::date((string) $code['valid_from']) : null,
            'valid_to'      => $code['valid_to'] ? Sanitizer::date((string) $code['valid_to']) : null,
            'max_uses'      => isset($code['max_uses']) && $code['max_uses'] !== '' ? (int) $code['max_uses'] : null,
            'usage_count'   => isset($code['usage_count']) ? (int) $code['usage_count'] : 0,
            'applies_to'    => $applies,
            'created_at'    => $code['created_at'] ?? current_time('mysql'),
            'updated_at'    => $code['updated_at'] ?? current_time('mysql'),
        ];
    }

    private static function generateNumber(array $existing, string $prefix): string
    {
        $numbers = array_map(static function ($entry) use ($prefix) {
            $number = (string) ($entry['number'] ?? '');
            if (strpos($number, $prefix . '-') === 0) {
                $parts = explode('-', $number);
                return isset($parts[1]) ? (int) $parts[1] : 0;
            }
            return 0;
        }, $existing);
        $next = max($numbers ?: [0]) + 1;
        return sprintf('%s-%04d', $prefix, $next);
    }

    private static function emptyState(): array
    {
        return [
            'invoices'       => [],
            'credit_notes'   => [],
            'discount_codes' => [],
            'settings'       => [
                'auto_invoice' => false,
                'auto_send'    => false,
                'batch_mode'   => 'manual',
            ],
            'ledger' => [
                'accounts'    => [],
                'exported_at' => null,
            ],
        ];
    }

    private static function normalizeState(array $state): array
    {
        $state = wp_parse_args($state, self::emptyState());
        $state['invoices'] = array_values(array_map([self::class, 'sanitizeInvoice'], $state['invoices'] ?? []));
        $state['credit_notes'] = array_values(array_map([self::class, 'sanitizeInvoice'], $state['credit_notes'] ?? []));
        $state['discount_codes'] = array_values(array_map([self::class, 'sanitizeDiscountCode'], $state['discount_codes'] ?? []));
        $state['settings'] = self::sanitizeSettings($state['settings'] ?? []);
        $state['ledger'] = self::sanitizeLedger($state['ledger'] ?? []);
        return $state;
    }

    private static function buildDefaultState(): array
    {
        $now = current_time('mysql');
        $today = current_time('Y-m-d');
        return [
            'invoices' => [
                [
                    'id' => wp_generate_uuid4(),
                    'number' => 'INV-0001',
                    'customer' => 'Auto Schule Muster',
                    'date' => $today,
                    'due_date' => $today,
                    'status' => 'open',
                    'currency' => 'CHF',
                    'items' => [
                        [
                            'description' => __('Fahrlektion 90min', 'bookando'),
                            'type' => 'service',
                            'reference' => 'DL-001',
                            'quantity' => 1,
                            'unit_price' => 150,
                            'tax_rate' => 7.7,
                        ],
                        [
                            'description' => __('Theoriekurs', 'bookando'),
                            'type' => 'course',
                            'reference' => 'CRS-100',
                            'quantity' => 1,
                            'unit_price' => 79,
                            'tax_rate' => 7.7,
                        ],
                    ],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'credit_notes' => [
                [
                    'id' => wp_generate_uuid4(),
                    'number' => 'CN-0001',
                    'customer' => 'Auto Schule Muster',
                    'date' => $today,
                    'due_date' => $today,
                    'status' => 'draft',
                    'currency' => 'CHF',
                    'items' => [
                        [
                            'description' => __('Gutschrift – abgesagte Fahrlektion', 'bookando'),
                            'type' => 'service',
                            'reference' => 'DL-001',
                            'quantity' => 1,
                            'unit_price' => -75,
                            'tax_rate' => 7.7,
                        ],
                    ],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'discount_codes' => [
                [
                    'id' => wp_generate_uuid4(),
                    'code' => 'WELCOME10',
                    'description' => __('Einführungsrabatt für neue Kunden', 'bookando'),
                    'discount_type' => 'percentage',
                    'value' => 10,
                    'valid_from' => $today,
                    'valid_to' => null,
                    'max_uses' => 50,
                    'usage_count' => 12,
                    'applies_to' => ['Fahrlektionen', 'Kurse'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'settings' => [
                'auto_invoice' => true,
                'auto_send' => false,
                'batch_mode' => 'weekly',
            ],
            'ledger' => [
                'accounts' => [],
                'exported_at' => null,
            ],
        ];
    }
}
