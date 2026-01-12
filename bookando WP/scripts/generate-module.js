#!/usr/bin/env node

/**
 * Bookando module scaffolder.
 *
 * Recreates a complete module structure under src/modules/<slug> that aligns with
 * the architecture defined in docs/Bookando-Plugin-Struktur.md.
 */

import { promises as fs } from 'fs'
import path from 'path'
import inquirer from 'inquirer'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const ROOT_DIR = path.resolve(__dirname, '..')
const MODULES_DIR = path.resolve(ROOT_DIR, 'src', 'modules')
const LICENSE_MAP_FILE = path.resolve(ROOT_DIR, 'src', 'Core', 'Licensing', 'license-features.php')

const FALLBACK_FEATURES = [
  'waitlist',
  'calendar_sync',
  'feedback',
  'mobile_app',
  'webhooks',
  'export_csv',
  'analytics_basic',
  'basic_payments',
  'online_payment',
  'integration_zoom',
  'integration_meet',
  'integration_teams',
  'rest_api_read',
  'notifications_whatsapp',
  'multi_tenant',
  'white_label',
  'rest_api_write',
  'export_pdf',
  'multi_calendar',
  'refunds',
  'custom_reports',
  'priority_support',
  'analytics_advanced',
  'student_offline',
  'competence_matrix',
  'grade_export',
  'digital_report',
  'progress_tracking',
  'advanced_security',
  'sso',
  'unlimited_domains'
]

const RESERVED_SLUGS = new Set([
  'bookando',
  'core',
  'admin',
  'login',
  'rest',
  'api',
  'wp',
  'wordpress'
])

const MODULE_GROUPS = Object.freeze([
  { value: 'core', name: 'core – Zentrale Verwaltung & Einstellungen' },
  { value: 'operations', name: 'operations – Buchungen, Kalender & Ressourcen' },
  { value: 'crm', name: 'crm – Kunden & Kommunikation' },
  { value: 'offers', name: 'offers – Produkte & Verkauf' }
])

function toPascalCase(value) {
  return value
    .replace(/[-_]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .split(' ')
    .filter(Boolean)
    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')
}

function toTitleCase(value) {
  const pascal = toPascalCase(value)
  return pascal.replace(/([A-Z])/g, (match, char, offset) => (offset > 0 ? ' ' : '') + char).trim()
}

async function fileExists(filePath) {
  try {
    await fs.access(filePath)
    return true
  } catch {
    return false
  }
}

async function loadLicenseMeta() {
  try {
    const raw = await fs.readFile(LICENSE_MAP_FILE, 'utf8')
    const plansSectionMatch = raw.match(/'plans'\s*=>\s*\[(.*)\]\s*\];/s)
    if (!plansSectionMatch) {
      throw new Error('No plans section found')
    }

    const plansSection = plansSectionMatch[1]
    const planRegex = /'([a-zA-Z0-9_]+)'\s*=>\s*\[(.*?)\n\s*\],/gs
    const plans = {}
    let planMatch

    const extractList = block => {
      if (!block) return []
      return block[1]
        .replace(/\/\/.*$/gm, '')
        .replace(/\n/g, ' ')
        .split(',')
        .map(item => item.replace(/['"\s]/g, ''))
        .filter(Boolean)
    }

    while ((planMatch = planRegex.exec(plansSection))) {
      const planName = planMatch[1]
      const block = planMatch[2]
      const modulesBlock = block.match(/'modules'\s*=>\s*\[((?:.|\n)*?)\]/)
      const featuresBlock = block.match(/'features'\s*=>\s*\[((?:.|\n)*?)\]/)

      plans[planName] = {
        modules: extractList(modulesBlock),
        features: extractList(featuresBlock)
      }
    }

    const resolvedPlans = {}

    const resolveList = (planName, key, visited = new Set()) => {
      if (!plans[planName] || visited.has(`${key}:${planName}`)) {
        return []
      }
      visited.add(`${key}:${planName}`)
      const source = plans[planName][key] || []
      const result = []
      for (const entry of source) {
        if (entry.startsWith('@')) {
          result.push(...resolveList(entry.slice(1), key, visited))
        } else {
          result.push(entry)
        }
      }
      return result
    }

    for (const planName of Object.keys(plans)) {
      resolvedPlans[planName] = {
        modules: Array.from(new Set(resolveList(planName, 'modules'))).sort(),
        features: Array.from(new Set(resolveList(planName, 'features'))).sort()
      }
    }

    const featureSet = new Set()
    Object.values(resolvedPlans).forEach(entry => {
      entry.features.forEach(feature => featureSet.add(feature))
    })

    return {
      plans: Object.keys(resolvedPlans),
      features: featureSet.size ? Array.from(featureSet).sort() : FALLBACK_FEATURES
    }
  } catch (error) {
    console.warn('⚠️  Konnte Lizenz-Metadaten nicht parsen:', error.message)
    return {
      plans: ['starter', 'pro', 'academy', 'enterprise'],
      features: FALLBACK_FEATURES
    }
  }
}

function sanitizeFeatureList(values) {
  if (!Array.isArray(values)) return []
  return Array.from(new Set(values.map(v => String(v).trim()).filter(Boolean))).sort()
}

function phpEscape(value) {
  return String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'")
}

function manifestJson(config) {
  const manifest = {
    slug: config.slug,
    plan: config.plan,
    version: '1.0.0',
    tenant_required: config.tenantRequired,
    license_required: config.licenseRequired,
    features_required: config.features,
    group: config.group,
    is_saas: config.isSaas,
    has_admin: true,
    supports_webhook: config.supportsWebhook,
    supports_offline: config.supportsOffline,
    supports_calendar: config.supportsCalendar,
    name: {
      default: config.nameDefault,
      ...(config.nameDe ? { de: config.nameDe } : {}),
      ...(config.nameEn ? { en: config.nameEn } : {})
    },
    alias: config.alias ? config.alias : {},
    description: {
      default: config.descriptionDefault,
      ...(config.descriptionDe ? { de: config.descriptionDe } : {}),
      ...(config.descriptionEn ? { en: config.descriptionEn } : {})
    },
    visible: true,
    menu_icon: config.menuIcon,
    menu_position: Number.isFinite(config.menuPosition)
      ? config.menuPosition
      : Number(config.menuPosition) || 0,
    dependencies: config.dependencies,
    tabs: [],
    is_submodule: config.isSubmodule,
    parent_module: config.parentModule || null
  }

  return JSON.stringify(manifest, null, 2) + '\n'
}
function modulePhpTemplate(config) {
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug};`,
    '',
    'use Bookando\\Core\\Base\\BaseModule;',
    `use Bookando\\Modules\\${config.slug}\\Admin\\Admin;`,
    `use Bookando\\Modules\\${config.slug}\\Api\\Api;`,
    `use Bookando\\Modules\\${config.slug}\\Capabilities;`,
    '',
    'class Module extends BaseModule',
    '{',
    '    public function boot(): void',
    '    {',
    '        $this->register();',
    '    }',
    '',
    '    public function register(): void',
    '    {',
    '        add_action(\'init\', [Capabilities::class, \'register\']);',
    '',
    '        if (is_admin() && class_exists(Admin::class)) {',
    '            add_action(\'bookando_register_module_menus\', [Admin::class, \'register_menu\']);',
    '            add_action(\'admin_enqueue_scripts\', [$this, \'enqueue_admin_assets\']);',
    '        }',
    '',
    '        add_action(\'rest_api_init\', [Api::class, \'register_routes\']);',
    '    }',
    '}',
    ''
  ].join('\n')
}

function adminPhpTemplate(config) {
  const title = phpEscape(config.nameDefault)
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug}\\Admin;`,
    '',
    'use Bookando\\Core\\Base\\BaseAdmin;',
    '',
    'class Admin extends BaseAdmin',
    '{',
    `    protected static function getPageTitle(): string    { return __('${title}', 'bookando'); }`,
    `    protected static function getMenuSlug(): string     { return 'bookando_${config.slug}'; }`,
    `    protected static function getCapability(): string   { return 'manage_bookando_${config.slug}'; }`,
    `    protected static function getTemplate(): string     { return 'admin-vue-container'; }`,
    `    protected static function getModuleSlug(): string   { return '${config.slug}'; }`,
    `    protected static function getMenuIcon(): string     { return '${phpEscape(config.menuIcon)}'; }`,
    `    protected static function getMenuPosition(): int    { return ${config.menuPosition}; }`,
    '',
    '    public static function register_menu(): void',
    '    {',
    '        \\Bookando\\Core\\Admin\\Menu::addModuleSubmenu([',
    '            \'page_title\' => static::getPageTitle(),',
    '            \'menu_title\' => static::getPageTitle(),',
    '            \'capability\' => static::getCapability(),',
    '            \'menu_slug\'  => static::getMenuSlug(),',
    '            \'callback\'   => [static::class, \'renderPage\'],',
    '            \'icon_url\'   => static::getMenuIcon(),',
    '            \'position\'   => static::getMenuPosition(),',
    '        ]);',
    '    }',
    '',
    '    public static function renderPage(): void',
    '    {',
    '        include __DIR__ . \'/../Templates/admin-vue-container.php\';',
    '    }',
    '}',
    ''
  ].join('\n')
}

function capabilitiesPhpTemplate(config) {
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug};`,
    '',
    'class Capabilities',
    '{',
    '    /**',
    '     * Liste aller Capabilities dieses Moduls.',
    '     */',
    '    public static function getAll(): array',
    '    {',
    '        return [',
    `            'manage_bookando_${config.slug}',`,
    `            'export_bookando_${config.slug}',`,
    '        ];',
    '    }',
    '',
    '    /**',
    '     * Rollen, die diese Rechte standardmäßig erhalten.',
    '     */',
    '    public static function getDefaultRoles(): array',
    '    {',
    "        return ['administrator', 'bookando_manager'];",
    '    }',
    '',
    '    /**',
    '     * Capabilities idempotent registrieren.',
    '     */',
    '    public static function register(): void',
    '    {',
    '        foreach (self::getDefaultRoles() as $roleName) {',
    '            $role = get_role($roleName);',
    '            if (!$role) {',
    '                continue;',
    '            }',
    '',
    '            foreach (self::getAll() as $capability) {',
    '                if (!$role->has_cap($capability)) {',
    '                    $role->add_cap($capability);',
    '                }',
    '            }',
    '        }',
    '    }',
    '}',
    ''
  ].join('\n')
}

function apiPhpTemplate(config) {
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug}\\Api;`,
    '',
    'use WP_REST_Request;',
    'use Bookando\\Core\\Licensing\\LicenseManager;',
    `use Bookando\\Modules\\${config.slug}\\RestHandler;`,
    '',
    'class Api',
    '{',
    '    public static function register_routes(): void',
    '    {',
    "        $namespace = 'bookando/v1';",
    `        $collection = '/${config.slug}';`,
    '',
    '        $permission = function () {',
    `            return current_user_can('manage_bookando_${config.slug}')`,
    `                && LicenseManager::isModuleAllowed('${config.slug}');`,
    '        };',
    '',
    '        register_rest_route($namespace, $collection, [',
    "            'methods'             => 'GET',",
    "            'permission_callback' => $permission,",
    "            'callback'            => [RestHandler::class, 'list'],",
    '        ]);',
    '',
    '        register_rest_route($namespace, $collection, [',
    "            'methods'             => 'POST',",
    "            'permission_callback' => $permission,",
    "            'callback'            => [RestHandler::class, 'create'],",
    '        ]);',
    '',
    "        register_rest_route($namespace, $collection . '/(?P<id>\\d+)', [",
    "            'methods'             => 'GET',",
    "            'permission_callback' => $permission,",
    "            'callback'            => [RestHandler::class, 'read'],",
    '        ]);',
    '',
    "        register_rest_route($namespace, $collection . '/(?P<id>\\d+)', [",
    "            'methods'             => 'PUT',",
    "            'permission_callback' => $permission,",
    "            'callback'            => [RestHandler::class, 'update'],",
    '        ]);',
    '',
    "        register_rest_route($namespace, $collection . '/(?P<id>\\d+)', [",
    "            'methods'             => 'DELETE',",
    "            'permission_callback' => $permission,",
    "            'callback'            => [RestHandler::class, 'delete'],",
    '        ]);',
    '',
    "        register_rest_route($namespace, $collection . '/bulk', [",
    "            'methods'             => 'POST',",
    "            'permission_callback' => $permission,",
    "            'callback'            => [RestHandler::class, 'bulk'],",
    '        ]);',
    '    }',
    '}',
    ''
  ].join('\n')
}

function restHandlerPhpTemplate(config) {
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug};`,
    '',
    'use WP_Error;',
    'use WP_REST_Request;',
    'use Bookando\\Core\\Api\\Response;',
    'use Bookando\\Core\\Licensing\\LicenseManager;',
    '',
    'class RestHandler',
    '{',
    `    protected const MODULE_SLUG = '${config.slug}';`,
    '',
    '    protected static function ensureAllowed(): true|WP_Error',
    '    {',
    `        if (!current_user_can('manage_bookando_${config.slug}')) {`,
    "            return new WP_Error('rest_forbidden', __('Missing capability.', 'bookando'), ['status' => 403]);",
    '        }',
    '',
    '        if (!LicenseManager::isModuleAllowed(self::MODULE_SLUG)) {',
    "            return new WP_Error('rest_forbidden_plan', __('Module not allowed for current plan.', 'bookando'), ['status' => 403]);",
    '        }',
    '',
    '        return true;',
    '    }',
    '',
    '    public static function list(WP_REST_Request $request)',
    '    {',
    '        $check = self::ensureAllowed();',
    '        if (is_wp_error($check)) {',
    '            return $check;',
    '        }',
    '',
    '        $items = [];',
    '        $meta = [\'total\' => 0];',
    '',
    '        return Response::ok([',
    '            \'data\' => $items,',
    '            \'meta\' => $meta,',
    '        ]);',
    '    }',
    '',
    '    public static function read(WP_REST_Request $request)',
    '    {',
    '        $check = self::ensureAllowed();',
    '        if (is_wp_error($check)) {',
    '            return $check;',
    '        }',
    '',
    '        $id = (int) $request->get_param(\'id\');',
    '        if ($id <= 0) {',
    "            return new WP_Error('rest_invalid_param', __('Invalid ID.', 'bookando'), ['status' => 400]);",
    '        }',
    '',
    '        return Response::ok([\'id\' => $id]);',
    '    }',
    '',
    '    public static function create(WP_REST_Request $request)',
    '    {',
    '        $check = self::ensureAllowed();',
    '        if (is_wp_error($check)) {',
    '            return $check;',
    '        }',
    '',
    '        $payload = (array) $request->get_json_params();',
    '        $createdId = 1;',
    '',
    '        return Response::created([\'id\' => $createdId, \'payload\' => $payload]);',
    '    }',
    '',
    '    public static function update(WP_REST_Request $request)',
    '    {',
    '        $check = self::ensureAllowed();',
    '        if (is_wp_error($check)) {',
    '            return $check;',
    '        }',
    '',
    '        $id = (int) $request->get_param(\'id\');',
    '        $payload = (array) $request->get_json_params();',
    '',
    '        return Response::ok([\'id\' => $id, \'payload\' => $payload]);',
    '    }',
    '',
    '    public static function delete(WP_REST_Request $request)',
    '    {',
    '        $check = self::ensureAllowed();',
    '        if (is_wp_error($check)) {',
    '            return $check;',
    '        }',
    '',
    '        $id = (int) $request->get_param(\'id\');',
    '        $hard = (bool) $request->get_param(\'hard\');',
    '',
    '        return Response::ok([\'deleted\' => $id, \'hard\' => $hard]);',
    '    }',
    '',
    '    public static function bulk(WP_REST_Request $request)',
    '    {',
    '        $check = self::ensureAllowed();',
    '        if (is_wp_error($check)) {',
    '            return $check;',
    '        }',
    '',
    '        $payload = (array) $request->get_json_params();',
    '',
    '        return Response::ok([\'bulk\' => $payload]);',
    '    }',
    '}',
    ''
  ].join('\n')
}
function installerPhpTemplate(config) {
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug};`,
    '',
    'class Installer',
    '{',
    '    public static function install(): void',
    '    {',
    '        global $wpdb;',
    "        require_once ABSPATH . 'wp-admin/includes/upgrade.php';",
    '',
    `        $table = $wpdb->prefix . 'bookando_${config.slug}';`,
    '        $charset = $wpdb->get_charset_collate();',
    '',
    '        $sql = "CREATE TABLE {$table} (',
    "            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,",
    "            title VARCHAR(191) NOT NULL,",
    "            status VARCHAR(32) DEFAULT 'active',",
    "            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,",
    "            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    `        ) {$charset};";`,
    '',
    '        dbDelta($sql);',
    '    }',
    '}',
    ''
  ].join('\n')
}

function modelPhpTemplate(config) {
  return [
    '<?php',
    `namespace Bookando\\Modules\\${config.slug};`,
    '',
    'use Bookando\\Core\\Model\\BaseModel;',
    '',
    `class Model extends BaseModel`,
    '{',
    '    protected string $tableName;',
    '',
    '    public function __construct()',
    '    {',
    '        parent::__construct();',
    `        $this->tableName = $this->table('${config.slug}');`,
    '    }',
    '',
    '    public function all(): array',
    '    {',
    '        $sql = "SELECT * FROM {$this->tableName} ORDER BY id DESC";',
    '        return $this->db->get_results($sql, ARRAY_A) ?: [];',
    '    }',
    '',
    '    public function find(int $id): ?array',
    '    {',
    '        $sql = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE id = %d", $id);',
    '        $row = $this->db->get_row($sql, ARRAY_A);',
    '        return $row ?: null;',
    '    }',
    '',
    '    public function create(array $data): int',
    '    {',
    '        $data = $this->sanitize($data);',
    '        $data[\'created_at\'] = $data[\'created_at\'] ?? $this->now();',
    '        $data[\'updated_at\'] = $data[\'updated_at\'] ?? $data[\'created_at\'];',
    '',
    '        $this->db->insert($this->tableName, $data);',
    '        return (int) $this->db->insert_id;',
    '    }',
    '',
    '    public function update(int $id, array $data): bool',
    '    {',
    '        $data = $this->sanitize($data);',
    '        $data[\'updated_at\'] = $this->now();',
    '        return (bool) $this->db->update($this->tableName, $data, [\'id\' => $id]);',
    '    }',
    '',
    '    public function delete(int $id, bool $hard = false): bool',
    '    {',
    '        if ($hard) {',
    '            return (bool) $this->db->delete($this->tableName, [\'id\' => $id]);',
    '        }',
    '',
    '        return (bool) $this->db->update($this->tableName, [\'status\' => \'archived\'], [\'id\' => $id]);',
    '    }',
    '',
    '    protected function sanitize(array $data): array',
    '    {',
    "        $allowed = ['title', 'status', 'created_at', 'updated_at'];",
    '        return array_intersect_key($data, array_flip($allowed));',
    '    }',
    '}',
    ''
  ].join('\n')
}

function adminTemplatePhp(config) {
  return String.raw`<?php
use Bookando\Core\Licensing\LicenseManager;

if (!isset($slug) || !$slug) {
    $slug = isset($_GET['page']) && strpos((string) $_GET['page'], 'bookando_') === 0
        ? substr($_GET['page'], strlen('bookando_'))
        : '${config.slug}';
}

$slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $slug);
$handle = 'bookando-' . $slug . '-app';

$bookando_vars = [
    'nonce'          => wp_create_nonce('wp_rest'),
    'module_allowed' => LicenseManager::isModuleAllowed($slug),
    'required_plan'  => LicenseManager::getLicensePlan(),
    'slug'           => $slug,
    'ajax_url'       => admin_url('admin-ajax.php'),
    'rest_url'       => rest_url('bookando/v1/' . $slug),
];

$inlineGuard = 'BOOKANDO_' . strtoupper($slug) . '_VARS_PRINTED';

if (!defined($inlineGuard)) {
    define($inlineGuard, true);

    $inline_js = sprintf(
        '(function(add){'
        . 'var w = window; w.BOOKANDO_VARS = w.BOOKANDO_VARS || {};'
        . 'if (w.BOOKANDO_VARS.lang != null) { delete add.lang; }'
        . 'if (w.BOOKANDO_VARS.wp_locale != null) { delete add.wp_locale; }'
        . 'for (var k in add) { if (Object.prototype.hasOwnProperty.call(add, k)) { w.BOOKANDO_VARS[k] = add[k]; } }'
        . '})(%s);',
        wp_json_encode($bookando_vars)
    );

    wp_add_inline_script($handle, $inline_js, 'before');
}

printf(
    '<div id="bookando-%1$s-root" data-module="%1$s" class="bookando-module-root">%2$s</div>',
    esc_attr($slug),
    esc_html__('Loading module…', 'bookando')
);
`
}
function readmeTemplate(config) {
  return `# ${config.nameDefault}\n\n` +
    'Dieses Modul wurde mit dem Bookando-Generator erstellt und enthält ein vollständiges Grundgerüst.\n\n' +
    '## Nächste Schritte\n\n' +
    '- Vue-Komponenten in `assets/vue/views` und `assets/vue/components` anpassen\n' +
    '- REST-Handler in `RestHandler.php` mit echter Geschäftslogik füllen\n' +
    '- Datenbank-Schema und Installer anpassen\n' +
    '- Tests und Dokumentation ergänzen\n'
}

function changelogTemplate(config) {
  return [
    `# Changelog – ${config.nameDefault}`,
    '',
    '## 1.0.0 – Initial scaffold',
    '',
    '- Modulstruktur mit Admin, REST, Vue, Store und Installer erstellt',
    ''
  ].join('\n')
}

function actionsTsTemplate(config) {
  return [
    `import { useModuleActions, getBulkActionOptions, type ActionKey } from '@core/Composables/useModuleActions'`,
    `import { use${config.pascalName}Store } from './assets/vue/store/store'`,
    '',
    `export function use${config.pascalName}Actions() {`,
    `  const store = use${config.pascalName}Store()`,
    `  const { loading, perform } = useModuleActions('${config.slug}')`,
    '',
    '  async function run(action: ActionKey, ids: number[] = [], payload?: unknown) {',
    '    const result = await perform(action, { ids, payload })',
    "    if (result.ok && ['save', 'soft_delete', 'hard_delete'].includes(action as string)) {",
    '      await store.load()',
    '    }',
    '    return result',
    '  }',
    '',
    '  return { loading, run }',
    '}',
    '',
    `export function ${config.slug}BulkOptions(t: (key: string) => string) {`,
    `  return getBulkActionOptions(t, '${config.slug}')`,
    '}',
    ''
  ].join('\n')
}

function composableTemplate(config) {
  return [
    `import { computed } from 'vue'`,
    `import { use${config.pascalName}Store } from '../assets/vue/store/store'`,
    '',
    `export function use${config.pascalName}Data() {`,
    `  const store = use${config.pascalName}Store()`,
    '  return {',
    '    store,',
    '    items: computed(() => store.items),',
    '    loading: computed(() => store.loading),',
    '    error: computed(() => store.error)',
    '  }',
    '}',
    ''
  ].join('\n')
}

function storeTemplate(config) {
  return [
    "import { defineStore } from 'pinia'",
    "import { ref } from 'vue'",
    `import type { ${config.pascalName} } from '../models/${config.pascalName}Model'`,
    `import { list${config.pascalName}, read${config.pascalName}, create${config.pascalName}, update${config.pascalName}, delete${config.pascalName} } from '../api/${config.pascalName}Api'`,
    '',
    `export const use${config.pascalName}Store = defineStore('${config.slug}', () => {`,
    `  const items = ref<${config.pascalName}[]>([])`,
    "  const current = ref<${config.pascalName} | null>(null)",
    "  const loading = ref(false)",
    "  const error = ref<string | null>(null)",
    '',
    '  async function load(params: Record<string, unknown> = {}) {',
    '    loading.value = true',
    '    error.value = null',
    '    try {',
    `      items.value = await list${config.pascalName}(params)`,
    '    } catch (err: any) {',
    "      error.value = err?.message || 'Fehler beim Laden.'",
    '      items.value = []',
    '    } finally {',
    '      loading.value = false',
    '    }',
    '  }',
    '',
    '  async function fetch(id: number) {',
    '    loading.value = true',
    '    try {',
    `      current.value = await read${config.pascalName}(id)`,
    '      return current.value',
    '    } finally {',
    '      loading.value = false',
    '    }',
    '  }',
    '',
    `  async function save(payload: Partial<${config.pascalName}>) {`,
    '    loading.value = true',
    '    error.value = null',
    '    try {',
    '      if (payload.id) {',
    `        await update${config.pascalName}(payload.id, payload)`,
    '      } else {',
    `        await create${config.pascalName}(payload)`,
    '      }',
    '      await load()',
    '      return true',
    '    } catch (err: any) {',
    "      error.value = err?.message || 'Speichern fehlgeschlagen.'",
    '      return false',
    '    } finally {',
    '      loading.value = false',
    '    }',
    '  }',
    '',
    '  async function remove(id: number, options: { hard?: boolean } = {}) {',
    '    loading.value = true',
    '    error.value = null',
    '    try {',
    `      await delete${config.pascalName}(id, options)`,
    '      await load()',
    '      return true',
    '    } catch (err: any) {',
    "      error.value = err?.message || 'Löschen fehlgeschlagen.'",
    '      return false',
    '    } finally {',
    '      loading.value = false',
    '    }',
    '  }',
    '',
    '  return { items, current, loading, error, load, fetch, save, remove }',
    '})',
    ''
  ].join('\n')
}

function mainTsTemplate(config) {
  return [
    "import { createApp } from 'vue'",
    "import { createPinia } from 'pinia'",
    "import { createI18n } from 'vue-i18n'",
    `import ${config.pascalName}View from './views/${config.pascalName}View.vue'`,
    "import { messages as coreMessages } from '@core/Design/i18n'",
    "import { bootLocaleFromBridge, initLocaleBridge } from '@core/Locale/bridge'",
    "import BookandoUI from '@core/Design/assets/vue/ui.js'",
    '',
    "import '../css/admin.scss'",
    "const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true }) as Record<string, any>",
    'let localMessages: Record<string, any> | undefined',
    'for (const key of Object.keys(localModules)) {',
    '  const mod = localModules[key]',
    '  if (mod?.messages) {',
    '    localMessages = mod.messages',
    '    break',
    '  }',
    '}',
    '',
    'function mergeMessages(core: Record<string, any>, local?: Record<string, any>) {',
    '  const output: Record<string, any> = {}',
    '  const locales = new Set([...(Object.keys(core || {})), ...(Object.keys(local || {}))])',
    '  for (const locale of locales) {',
    '    output[locale] = { ...(core?.[locale] || {}), ...(local?.[locale] || {}) }',
    '  }',
    '  return output',
    '}',
    '',
    'const messages = mergeMessages(coreMessages, localMessages)',
    'const { i18nLocale } = bootLocaleFromBridge({ available: Object.keys(messages), fallback: \'de\' })',
    '',
    "const i18n = createI18n({ legacy: false, locale: i18nLocale, fallbackLocale: 'de', messages })",
    '',
    "const slug = (window as any).BOOKANDO_VARS?.slug || '${config.slug}'",
    'const mountSelector = `#bookando-${slug}-root`',
    'const root = document.querySelector(mountSelector) as HTMLElement | null',
    '',
    'if (root && !root.hasAttribute(\'data-v-app\')) {',
    `  const app = createApp(${config.pascalName}View)`,
    '  app.use(createPinia()).use(i18n).use(BookandoUI)',
    '  initLocaleBridge(i18n)',
    '  app.mount(root)',
    '} else {',
    "  console.warn('[Bookando] Mountpoint nicht gefunden oder bereits gemountet:', mountSelector)",
    '}',
    ''
  ].join('\n')
}
function viewVueTemplate(config) {
  return String.raw`<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />
      <section
        v-else
        class="bookando-container bookando-p-md"
      >
        <AppPageHeader
          :title="t('mod.${config.slug}.title')"
          hide-brand-below="md"
        />
        <div class="bookando-space-y-md">
          <p class="bookando-text">{{ t('mod.${config.slug}.empty_state') }}</p>
          <AppButton
            icon="plus"
            variant="primary"
            @click="openCreateDialog"
          >
            {{ t('mod.${config.slug}.cta_add') }}
          </AppButton>
        </div>
      </section>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { use${config.pascalName}Store } from '../store/store'
import { use${config.pascalName}Actions } from '../../actions'

const store = use${config.pascalName}Store()
const { t } = useI18n()
const { run } = use${config.pascalName}Actions()

const moduleAllowed = computed(() => window.BOOKANDO_VARS?.module_allowed ?? true)
const requiredPlan = computed(() => window.BOOKANDO_VARS?.required_plan ?? 'starter')

store.load()

function openCreateDialog() {
  run('save', [], {})
}
</script>
`
}

function scssTemplate(config) {
  return [
    "@use 'sass:map';",
    "@use '@scss/variables' as *;",
    "@use '@scss/mixins' as *;",
    '',
    `// Basisstyles für das Modul ${config.slug}`,
    `.bookando-${config.slug}-module {`,
    '  display: flex;',
    '  flex-direction: column;',
    '  gap: map.get($bookando-spacing, md);',
    '}',
    ''
  ].join('\n')
}

function apiTsTemplate(config) {
  return [
    "import http from '@assets/http'",
    `import type { ${config.pascalName} } from '../models/${config.pascalName}Model'`,
    '',
    `const client = http.module('${config.slug}')`,
    '',
    `export async function list${config.pascalName}(params: Record<string, any> = {}) {`,
    `  const response = await client.get('${config.slug}', params)`,
    '  const data = response?.data',
    `  if (Array.isArray(data?.data)) return data.data as ${config.pascalName}[]`,
    `  if (Array.isArray(data)) return data as ${config.pascalName}[]`,
    `  return [] as ${config.pascalName}[]`,
    '}',
    '',
    `export async function read${config.pascalName}(id: number) {`,
    `  const response = await client.get('${config.slug}/' + id)`,
    `  return (response?.data || null) as ${config.pascalName} | null`,
    '}',
    '',
    `export async function create${config.pascalName}(payload: Partial<${config.pascalName}>) {`,
    `  const response = await client.post('${config.slug}', payload)`,
    '  return response?.data',
    '}',
    '',
    `export async function update${config.pascalName}(id: number, payload: Partial<${config.pascalName}>) {`,
    `  const response = await client.put('${config.slug}/' + id, payload)`,
    '  return response?.data',
    '}',
    '',
    `export async function delete${config.pascalName}(id: number, options: { hard?: boolean } = {}) {`,
    '  const params = options.hard ? { hard: 1 } : undefined',
    `  const response = await client.del('${config.slug}/' + id, params)`,
    '  return response?.data',
    '}',
    ''
  ].join('\n')
}

function modelTsTemplate(config) {
  return [
    `export interface ${config.pascalName} {`,
    '  id: number',
    "  title: string",
    "  status: 'active' | 'inactive' | 'archived'",
    '  created_at: string',
    '  updated_at: string',
    '}',
    '',
    `export type ${config.pascalName}Payload = Partial<Omit<${config.pascalName}, 'id' | 'created_at' | 'updated_at'>>`,
    ''
  ].join('\n')
}

function i18nLocalTemplate(config) {
  return [
    'export const messages = {',
    '  de: {',
    `    mod: {`,
    `      ${config.slug}: {`,
    "        title: '${phpEscape(config.nameDe || config.nameDefault)}',",
    "        empty_state: 'Noch keine Einträge vorhanden.',",
    "        cta_add: 'Neuen Eintrag anlegen'",
    '      }',
    '    }',
    '  },',
    '  en: {',
    '    mod: {',
    `      ${config.slug}: {`,
    "        title: '${phpEscape(config.nameEn || config.nameDefault)}',",
    "        empty_state: 'No entries yet.',",
    "        cta_add: 'Add entry'",
    '      }',
    '    }',
    '  }',
    '}',
    ''
  ].join('\n')
}

function docsReadmeTemplate(config) {
  return [
    `# ${config.nameDefault} – Modul-Dokumentation`,
    '',
    '## Überblick',
    '',
    `- Slug: \`${config.slug}\``,
    `- Lizenzplan: \`${config.plan}\``,
    `- Gruppe: \`${config.group}\``,
    '',
    '## Implementierungshinweise',
    '',
    '- REST-API: siehe `Api/Api.php` und `RestHandler.php`',
    '- Frontend: Vue 3 SPA unter `assets/vue`',
    '- Styles: `assets/css/admin.scss`',
    '- Datenmodell: `Model.php`',
    '- Installer: `Installer.php`',
    ''
  ].join('\n')
}

function cloudConfigTemplate(config) {
  return JSON.stringify({
    slug: config.slug,
    webhooks: [
      {
        event: `${config.slug}.updated`,
        url: 'https://example.com/webhooks/${config.slug}',
        secret: 'change-me'
      }
    ],
    notes: 'Passe die Webhook-Konfiguration an die Anforderungen deines Mandanten an.'
  }, null, 2) + '\n'
}

function webhookSkeletonTemplate(config) {
  return [
    '<?php',
    `// Webhook-Skeleton für das Modul ${config.slug}`,
    '',
    'return new class {',
    '    public function handle(array $payload): void',
    '    {',
    '        // TODO: Webhook-Logik implementieren',
    '    }',
    '};',
    ''
  ].join('\n')
}

async function promptForConfig(meta) {
  const existingModules = await fs.readdir(MODULES_DIR)

  const { slug } = await inquirer.prompt([
    {
      type: 'input',
      name: 'slug',
      message: 'Modul-Slug (kebab_case, z. B. "events")',
      validate: input => {
        const value = String(input || '').trim()
        if (!/^[a-z][a-z0-9_-]+$/.test(value)) {
          return 'Nur Kleinbuchstaben, Zahlen, Bindestrich und Unterstrich erlaubt.'
        }
        if (RESERVED_SLUGS.has(value)) {
          return 'Slug ist reserviert.'
        }
        if (existingModules.includes(value)) {
          return 'Ein Modul mit diesem Slug existiert bereits.'
        }
        return true
      }
    }
  ])

  const pascalName = toPascalCase(slug)
  const defaultName = toTitleCase(slug)

  const answers = await inquirer.prompt([
    {
      type: 'list',
      name: 'plan',
      message: 'Lizenzplan',
      choices: meta.plans,
      default: meta.plans.includes('starter') ? 'starter' : meta.plans[0]
    },
    {
      type: 'list',
      name: 'group',
      message: 'Modul-Gruppe',
      choices: MODULE_GROUPS,
      default: 'core'
    },
    {
      type: 'confirm',
      name: 'tenantRequired',
      message: 'Mandantenbindung erforderlich?',
      default: true
    },
    {
      type: 'confirm',
      name: 'licenseRequired',
      message: 'Lizenzprüfung aktivieren?',
      default: true
    },
    {
      type: 'confirm',
      name: 'isSaas',
      message: 'Im SaaS-Modus verfügbar?',
      default: true
    },
    {
      type: 'checkbox',
      name: 'features',
      message: 'Benötigte Features (Leer lassen für keine)',
      choices: meta.features,
      pageSize: 15
    },
    {
      type: 'input',
      name: 'dependenciesRaw',
      message: 'Weitere Modul-Abhängigkeiten (kommagetrennt, optional)'
    },
    {
      type: 'confirm',
      name: 'supportsWebhook',
      message: 'Webhooks vorgesehen?',
      default: false
    },
    {
      type: 'confirm',
      name: 'supportsOffline',
      message: 'Offline-Unterstützung?',
      default: false
    },
    {
      type: 'confirm',
      name: 'supportsCalendar',
      message: 'Kalender-Integration?',
      default: false
    },
    {
      type: 'input',
      name: 'nameDefault',
      message: 'Modulname (default)',
      default: defaultName
    },
    {
      type: 'input',
      name: 'nameDe',
      message: 'Modulname (Deutsch)',
      default: defaultName
    },
    {
      type: 'input',
      name: 'nameEn',
      message: 'Modulname (Englisch)',
      default: defaultName
    },
    {
      type: 'input',
      name: 'descriptionDefault',
      message: 'Beschreibung (default)',
      default: `${defaultName} module`
    },
    {
      type: 'input',
      name: 'descriptionDe',
      message: 'Beschreibung (Deutsch)'
    },
    {
      type: 'input',
      name: 'descriptionEn',
      message: 'Beschreibung (Englisch)'
    },
    {
      type: 'input',
      name: 'menuIcon',
      message: 'Admin-Menü Icon (Dashicon Class)',
      default: 'dashicons-admin-generic'
    },
    {
      type: 'number',
      name: 'menuPosition',
      message: 'Admin-Menü Position',
      default: 30
    },
    {
      type: 'confirm',
      name: 'isSubmodule',
      message: 'Ist ein Submodul?',
      default: false
    },
    {
      type: answers => answers.isSubmodule ? 'input' : 'confirm',
      name: 'parentModule',
      message: answers => answers.isSubmodule ? 'Parent-Slug für Submodul' : '',
      when: answers => answers.isSubmodule,
      default: ''
    },
    {
      type: 'confirm',
      name: 'addDocs',
      message: 'Docs-Verzeichnis erstellen?',
      default: true
    }
  ])

  const dependencies = (answers.dependenciesRaw || '')
    .split(',')
    .map(value => value.trim())
    .filter(Boolean)

  return {
    slug,
    pascalName,
    plan: answers.plan,
    group: answers.group,
    tenantRequired: answers.tenantRequired,
    licenseRequired: answers.licenseRequired,
    isSaas: answers.isSaas,
    features: sanitizeFeatureList(answers.features),
    supportsWebhook: answers.supportsWebhook,
    supportsOffline: answers.supportsOffline,
    supportsCalendar: answers.supportsCalendar,
    nameDefault: answers.nameDefault,
    nameDe: answers.nameDe,
    nameEn: answers.nameEn,
    descriptionDefault: answers.descriptionDefault || '',
    descriptionDe: answers.descriptionDe || '',
    descriptionEn: answers.descriptionEn || '',
    menuIcon: answers.menuIcon,
    menuPosition: Number.isFinite(answers.menuPosition) ? Number(answers.menuPosition) : 30,
    isSubmodule: answers.isSubmodule,
    parentModule: answers.isSubmodule ? (answers.parentModule || null) : null,
    addDocs: answers.addDocs,
    dependencies,
    alias: {},
    menuGroup: answers.group
  }
}

async function writeFile(filePath, content) {
  await fs.mkdir(path.dirname(filePath), { recursive: true })
  await fs.writeFile(filePath, content, 'utf8')
}

async function createModule(config) {
  const moduleDir = path.join(MODULES_DIR, config.slug)
  if (await fileExists(moduleDir)) {
    throw new Error(`Verzeichnis ${moduleDir} existiert bereits.`)
  }

  const files = new Map()
  files.set(path.join(moduleDir, 'module.json'), manifestJson(config))
  files.set(path.join(moduleDir, 'Module.php'), modulePhpTemplate(config))
  files.set(path.join(moduleDir, 'Capabilities.php'), capabilitiesPhpTemplate(config))
  files.set(path.join(moduleDir, 'RestHandler.php'), restHandlerPhpTemplate(config))
  files.set(path.join(moduleDir, 'Installer.php'), installerPhpTemplate(config))
  files.set(path.join(moduleDir, 'Model.php'), modelPhpTemplate(config))
  files.set(path.join(moduleDir, 'README.md'), readmeTemplate(config))
  files.set(path.join(moduleDir, 'CHANGELOG.md'), changelogTemplate(config))
  files.set(path.join(moduleDir, 'actions.ts'), actionsTsTemplate(config))
  files.set(path.join(moduleDir, 'composables', `use${config.pascalName}Data.ts`), composableTemplate(config))

  files.set(path.join(moduleDir, 'Admin', 'Admin.php'), adminPhpTemplate(config))
  files.set(path.join(moduleDir, 'Api', 'Api.php'), apiPhpTemplate(config))
  files.set(path.join(moduleDir, 'Templates', 'admin-vue-container.php'), adminTemplatePhp(config))

  files.set(path.join(moduleDir, 'assets', 'vue', 'main.ts'), mainTsTemplate(config))
  files.set(path.join(moduleDir, 'assets', 'vue', 'store', 'store.ts'), storeTemplate(config))
  files.set(path.join(moduleDir, 'assets', 'vue', 'views', `${config.pascalName}View.vue`), viewVueTemplate(config))
  files.set(path.join(moduleDir, 'assets', 'vue', 'api', `${config.pascalName}Api.ts`), apiTsTemplate(config))
  files.set(path.join(moduleDir, 'assets', 'vue', 'models', `${config.pascalName}Model.ts`), modelTsTemplate(config))
  files.set(path.join(moduleDir, 'assets', 'vue', 'i18n.local.ts'), i18nLocalTemplate(config))
  files.set(path.join(moduleDir, 'assets', 'css', 'admin.scss'), scssTemplate(config))

  if (config.addDocs) {
    files.set(path.join(moduleDir, 'docs', 'README.md'), docsReadmeTemplate(config))
  }

  files.set(path.join(moduleDir, 'Cloud', 'cloud-config.json'), cloudConfigTemplate(config))
  files.set(path.join(moduleDir, 'Cloud', 'webhook-skeleton.php'), webhookSkeletonTemplate(config))

  for (const [filePath, content] of files.entries()) {
    await writeFile(filePath, content)
  }

  return moduleDir
}

async function main() {
  try {
    const meta = await loadLicenseMeta()
    const config = await promptForConfig(meta)
    const targetDir = await createModule(config)
    console.log(`✅ Modul "${config.slug}" wurde erstellt unter ${path.relative(ROOT_DIR, targetDir)}`)
  } catch (error) {
    console.error('❌ Fehler beim Erstellen des Moduls:', error.message)
    process.exitCode = 1
  }
}

if (import.meta.url === `file://${process.argv[1]}`) {
  await main()
}

