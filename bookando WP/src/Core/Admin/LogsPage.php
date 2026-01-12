<?php

namespace Bookando\Core\Admin;

use Bookando\Core\Service\ActivityLogger;
use Bookando\Core\Tenant\TenantManager;

class LogsPage
{
    private const MENU_SLUG = 'bookando-activity-log';
    private const DEFAULT_LIMIT = 50;

    public static function register(): void
    {
        Menu::addModuleSubmenu([
            'page_title' => __('Bookando Aktivitätsprotokoll', 'bookando'),
            'menu_title' => __('Aktivitätslog', 'bookando'),
            'capability' => 'manage_options',
            'menu_slug'  => self::MENU_SLUG,
            'callback'   => [self::class, 'render'],
        ]);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'bookando'));
        }

        $state = self::resolveFilters();

        if (isset($_GET['bookando_log_export']) && $_GET['bookando_log_export'] === 'csv') {
            self::exportCsv($state);
            return;
        }

        $entries = ActivityLogger::recent($state['limit'], $state['filters']);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Aktivitätsprotokoll', 'bookando') . '</h1>';
        echo '<p class="description">' . esc_html__(
            'Überwache die letzten Vorgänge im System. Filtere nach Schweregrad, Kontext, Zeitraum oder Modul und exportiere die Daten bei Bedarf als CSV.',
            'bookando'
        ) . '</p>';

        $exportUrl = self::buildExportUrl($state);

        self::renderFilters($state, $exportUrl);
        self::renderTable($entries);
        self::renderIntegrationHints();

        echo '</div>';
    }

    private static function resolveFilters(): array
    {
        // SICHERHEIT: Verwende filter_input() statt direktem $_GET-Zugriff
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, [
            'options' => ['default' => self::DEFAULT_LIMIT, 'min_range' => 1, 'max_range' => 500]
        ]);
        if (!$limit || $limit <= 0) {
            $limit = self::DEFAULT_LIMIT;
        }

        $severity = [];
        $rawSeverity = $_GET['severity'] ?? [];
        if (!is_array($rawSeverity)) {
            $rawSeverity = [$rawSeverity];
        }
        foreach ($rawSeverity as $value) {
            $value = strtolower(sanitize_text_field((string) $value));
            if (in_array($value, [
                ActivityLogger::LEVEL_INFO,
                ActivityLogger::LEVEL_WARNING,
                ActivityLogger::LEVEL_ERROR,
            ], true)) {
                $severity[] = $value;
            }
        }

        $context = filter_input(INPUT_GET, 'context', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $moduleSlug = filter_input(INPUT_GET, 'module_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $message = filter_input(INPUT_GET, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

        $dateFrom = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
        $dateTo = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

        $tenantScope = filter_input(INPUT_GET, 'tenant_scope', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'current';
        $tenantInput = filter_input(INPUT_GET, 'tenant_id', FILTER_VALIDATE_INT) ?: 0;
        $tenantId = TenantManager::currentTenantId();
        if ($tenantScope === 'all') {
            $tenantId = null;
        } elseif ($tenantScope === 'custom') {
            if ($tenantInput > 0) {
                $tenantId = $tenantInput;
            }
        }

        $filters = [
            'tenant_id'     => $tenantId,
            'include_global'=> true,
        ];

        if ($severity !== []) {
            $filters['severity'] = $severity;
        }
        if ($context !== '') {
            $filters['context'] = $context;
        }
        if ($moduleSlug !== '') {
            $filters['module_slug'] = $moduleSlug;
        }
        if ($message !== '') {
            $filters['message'] = $message;
        }
        if ($dateFrom !== '') {
            $filters['since'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $filters['until'] = $dateTo;
        }

        return [
            'limit'        => $limit,
            'severity'     => $severity,
            'context'      => $context,
            'module_slug'  => $moduleSlug,
            'message'      => $message,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'tenant_scope' => $tenantScope,
            'tenant_id'    => $tenantId,
            'tenant_input' => $tenantInput > 0 ? (string) $tenantInput : '',
            'filters'      => $filters,
        ];
    }

    private static function renderFilters(array $state, string $exportUrl): void
    {
        $severity = $state['severity'];
        $limit = (int) $state['limit'];

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::MENU_SLUG) . '" />';
        echo '<div class="tablenav top">';
        echo '<div class="alignleft actions">';

        echo '<label for="bookando-log-context" style="margin-right:12px;">' . esc_html__('Kontext', 'bookando') . '</label>';
        echo '<input type="text" id="bookando-log-context" name="context" value="' . esc_attr($state['context']) . '" />';

        echo '<label for="bookando-log-module" style="margin:0 12px 0 24px;">' . esc_html__('Modul', 'bookando') . '</label>';
        echo '<input type="text" id="bookando-log-module" name="module_slug" value="' . esc_attr($state['module_slug']) . '" size="12" />';

        echo '<label for="bookando-log-message" style="margin:0 12px 0 24px;">' . esc_html__('Nachricht', 'bookando') . '</label>';
        echo '<input type="text" id="bookando-log-message" name="message" value="' . esc_attr($state['message']) . '" size="14" />';

        echo '<label style="margin:0 12px 0 24px;">' . esc_html__('Severity', 'bookando') . '</label>';
        foreach ([
            ActivityLogger::LEVEL_INFO    => __('Info', 'bookando'),
            ActivityLogger::LEVEL_WARNING => __('Warnung', 'bookando'),
            ActivityLogger::LEVEL_ERROR   => __('Fehler', 'bookando'),
        ] as $level => $label) {
            $checked = in_array($level, $severity, true) ? ' checked="checked"' : '';
            echo '<label style="margin-right:8px;"><input type="checkbox" name="severity[]" value="' . esc_attr($level) . '"' . $checked . ' /> ' . esc_html($label) . '</label>';
        }

        echo '<label for="bookando-log-date-from" style="margin:0 12px 0 24px;">' . esc_html__('Von', 'bookando') . '</label>';
        echo '<input type="date" id="bookando-log-date-from" name="date_from" value="' . esc_attr($state['date_from']) . '" />';

        echo '<label for="bookando-log-date-to" style="margin:0 12px 0 24px;">' . esc_html__('Bis', 'bookando') . '</label>';
        echo '<input type="date" id="bookando-log-date-to" name="date_to" value="' . esc_attr($state['date_to']) . '" />';

        echo '<label for="bookando-log-limit" style="margin:0 12px 0 24px;">' . esc_html__('Limit', 'bookando') . '</label>';
        echo '<select id="bookando-log-limit" name="limit">';
        foreach ([25, 50, 100, 200, 500] as $option) {
            $selected = ($limit === $option) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr($option) . '"' . $selected . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';

        echo '<label for="bookando-log-tenant-scope" style="margin:0 12px 0 24px;">' . esc_html__('Mandant', 'bookando') . '</label>';
        echo '<select id="bookando-log-tenant-scope" name="tenant_scope">';
        $scopes = [
            'current' => __('Aktueller Mandant', 'bookando'),
            'all'     => __('Alle Mandanten', 'bookando'),
            'custom'  => __('Spezifischer Mandant', 'bookando'),
        ];
        foreach ($scopes as $value => $label) {
            $selected = ($state['tenant_scope'] === $value) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';

        $customTenantId = $state['tenant_input'];
        echo '<input type="number" min="1" step="1" name="tenant_id" placeholder="' . esc_attr__('Tenant-ID', 'bookando') . '" value="' . esc_attr($customTenantId) . '" style="width:110px; margin-left:8px;" />';

        echo '<button type="submit" class="button button-primary" style="margin-left:16px;">' . esc_html__('Filtern', 'bookando') . '</button>';
        echo '<a class="button" style="margin-left:8px;" href="' . esc_url(menu_page_url(self::MENU_SLUG, false)) . '">' . esc_html__('Zurücksetzen', 'bookando') . '</a>';
        echo '<a class="button button-secondary" style="margin-left:8px;" href="' . esc_url($exportUrl) . '">' . esc_html__('Als CSV exportieren', 'bookando') . '</a>';

        echo '</div>';
        echo '</div>';
        echo '</form>';
    }

    private static function renderTable(array $entries): void
    {
        if ($entries === []) {
            echo '<p>' . esc_html__('Keine Logeinträge gefunden.', 'bookando') . '</p>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Zeitpunkt', 'bookando') . '</th>';
        echo '<th>' . esc_html__('Severity', 'bookando') . '</th>';
        echo '<th>' . esc_html__('Mandant', 'bookando') . '</th>';
        echo '<th>' . esc_html__('Kontext', 'bookando') . '</th>';
        echo '<th>' . esc_html__('Modul', 'bookando') . '</th>';
        echo '<th>' . esc_html__('Nachricht', 'bookando') . '</th>';
        echo '<th>' . esc_html__('Payload', 'bookando') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        $dateFormat = get_option('date_format');
        $timeFormat = get_option('time_format');

        foreach ($entries as $entry) {
            $payload = $entry['payload'] !== null
                ? wp_json_encode($entry['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $entry['payload_raw'];

            $formattedDate = '';
            if (!empty($entry['logged_at'])) {
                $format = trim($dateFormat . ' ' . $timeFormat);
                if ($format === '') {
                    $format = 'Y-m-d H:i:s';
                }
                $formattedDate = mysql2date($format, $entry['logged_at']);
                if (!is_string($formattedDate)) {
                    $formattedDate = $entry['logged_at'];
                }
            }

            $tenantLabel = isset($entry['tenant_id']) && $entry['tenant_id'] !== null && $entry['tenant_id'] !== ''
                ? (string) $entry['tenant_id']
                : '—';

            $moduleLabel = $entry['module_slug'] !== null && $entry['module_slug'] !== ''
                ? $entry['module_slug']
                : '—';

            $payloadDisplay = $payload !== null ? $payload : '';

            echo '<tr>';
            echo '<td>' . esc_html($formattedDate) . '</td>';
            echo '<td><span class="bookando-log-severity bookando-log-severity-' . esc_attr($entry['severity']) . '">' . esc_html($entry['severity']) . '</span></td>';
            echo '<td>' . esc_html($tenantLabel) . '</td>';
            echo '<td>' . esc_html($entry['context']) . '</td>';
            echo '<td>' . esc_html($moduleLabel) . '</td>';
            echo '<td>' . esc_html($entry['message']) . '</td>';
            echo '<td><pre style="max-width:420px; white-space:pre-wrap;">' . esc_html($payloadDisplay) . '</pre></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    private static function renderIntegrationHints(): void
    {
        echo '<h2 style="margin-top:2em;">' . esc_html__('Integrationshinweise', 'bookando') . '</h2>';
        echo '<p>' . esc_html__(
            'Nutze ActivityLogger::log() in WP-CLI-Kommandos, Cronjobs oder Webhook-Handlern, um Vorgänge tenant-sicher zu verfolgen. Über die Filter `bookando_tenant_id_resolved` und `bookando_tenant_allow_header_switch` lassen sich externe Systeme einbinden.',
            'bookando'
        ) . '</p>';
    }

    private static function buildExportUrl(array $state): string
    {
        $params = [
            'page'         => self::MENU_SLUG,
            'limit'        => $state['limit'],
            'tenant_scope' => $state['tenant_scope'],
        ];

        if ($state['severity'] !== []) {
            $params['severity'] = $state['severity'];
        }
        if ($state['context'] !== '') {
            $params['context'] = $state['context'];
        }
        if ($state['module_slug'] !== '') {
            $params['module_slug'] = $state['module_slug'];
        }
        if ($state['message'] !== '') {
            $params['message'] = $state['message'];
        }
        if ($state['date_from'] !== '') {
            $params['date_from'] = $state['date_from'];
        }
        if ($state['date_to'] !== '') {
            $params['date_to'] = $state['date_to'];
        }
        if ($state['tenant_scope'] === 'custom' && !empty($state['tenant_input'])) {
            $params['tenant_id'] = (int) $state['tenant_input'];
        }

        $params['bookando_log_export'] = 'csv';

        return add_query_arg($params, menu_page_url(self::MENU_SLUG, false));
    }

    private static function exportCsv(array $state): void
    {
        $entries = ActivityLogger::recent($state['limit'], $state['filters']);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookando-activity-log-' . gmdate('Ymd-His') . '.csv"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            return;
        }

        fputcsv($output, [
            'id',
            'logged_at',
            'severity',
            'tenant_id',
            'context',
            'module_slug',
            'message',
            'payload',
        ]);

        foreach ($entries as $entry) {
            $payload = $entry['payload'] !== null
                ? wp_json_encode($entry['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $entry['payload_raw'];

            fputcsv($output, [
                $entry['id'],
                $entry['logged_at'],
                $entry['severity'],
                $entry['tenant_id'],
                $entry['context'],
                $entry['module_slug'],
                $entry['message'],
                $payload,
            ]);
        }

        fclose($output);
        exit;
    }
}
