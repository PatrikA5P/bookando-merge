<?php
declare(strict_types=1);

use Bookando\Core\Licensing\LicenseManager;

// Erwartet optionale Variablen vor dem Include:
// - $moduleSlug (string)
// - $moduleData (array)

// ---------------------------------------------
// Slug bestimmen (Fallback: aktuelles Admin-Page-Slug)
// ---------------------------------------------
if (!isset($moduleSlug) || !is_string($moduleSlug) || $moduleSlug === '') {
    $moduleSlug = isset($_GET['page']) && is_string($_GET['page']) && strpos($_GET['page'], 'bookando_') === 0
        ? substr($_GET['page'], strlen('bookando_'))
        : (defined('BOOKANDO_MODULE_SLUG') ? (string) BOOKANDO_MODULE_SLUG : 'bookando');
}

$moduleSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $moduleSlug);
$moduleSlug = $moduleSlug !== '' ? $moduleSlug : 'bookando';

// ---------------------------------------------
// Modul-Daten vorbereiten
// ---------------------------------------------
$moduleData = isset($moduleData) && is_array($moduleData) ? $moduleData : [];
$restNamespace = isset($moduleData['rest_namespace']) && is_string($moduleData['rest_namespace'])
    ? trim($moduleData['rest_namespace'], " \/")
    : 'bookando/v1';
$restBase = isset($moduleData['rest_base']) && is_string($moduleData['rest_base'])
    ? trim($moduleData['rest_base'], " \/")
    : $moduleSlug;

$restPath = $restNamespace !== '' ? $restNamespace : '';
if ($restBase !== '') {
    $restPath = $restPath !== '' ? $restPath . '/' . $restBase : $restBase;
}
$restUrl = rest_url($restPath);

$handle = "bookando-{$moduleSlug}-app";

// ---------------------------------------------
// Sprache bestimmen (Bookando-User → WP-Fallback)
// ---------------------------------------------
$bookandoUserLang = null;
$wpLocale = function_exists('get_user_locale') ? get_user_locale() : get_locale();

if (function_exists('get_current_user_id')) {
    $wpUserId = get_current_user_id();
    if ($wpUserId) {
        global $wpdb;
        if (isset($wpdb) && $wpdb instanceof \wpdb) {
            $prefix = $wpdb->prefix . 'bookando_';
            $bookandoUserLang = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT language FROM {$prefix}users WHERE external_id = %s LIMIT 1",
                    (string) $wpUserId
                )
            );
        }
    }
}

$lang = $bookandoUserLang;
if (empty($lang) || $lang === 'system') {
    $lang = $wpLocale;
}

// ---------------------------------------------
// Bridge-Variablen aufbauen und mergen
// ---------------------------------------------
$defaults = [
    'nonce'          => wp_create_nonce('wp_rest'),
    'module_allowed' => LicenseManager::isModuleAllowed($moduleSlug),
    'required_plan'  => LicenseManager::getLicensePlan(),
    'tabs'           => [],
    'slug'           => $moduleSlug,
    'lang'           => $lang,
    'wp_locale'      => $wpLocale,
    'ajax_url'       => admin_url('admin-ajax.php'),
    'rest_namespace' => $restNamespace,
    'rest_base'      => $restBase,
    'rest_url'       => $restUrl,
    'rest_root'      => rest_url(),
    'origin'         => home_url('/'),
    'debug'          => defined('WP_DEBUG') && WP_DEBUG,
];

if (isset($moduleData['vars']) && is_array($moduleData['vars'])) {
    $defaults = array_replace_recursive($defaults, $moduleData['vars']);
}

// Zentral gesteuerte Werte NICHT überschreibbar machen
$defaults['slug'] = $moduleSlug;
$defaults['lang'] = $lang;
$defaults['wp_locale'] = $wpLocale;
$defaults['rest_namespace'] = $restNamespace;
$defaults['rest_base'] = $restBase;
$defaults['rest_url'] = $restUrl;
$defaults['rest_root'] = rest_url();
$defaults['origin'] = home_url('/');

$inlineGuard = isset($moduleData['inline_guard']) && is_string($moduleData['inline_guard'])
    ? $moduleData['inline_guard']
    : 'BOOKANDO_' . strtoupper($moduleSlug) . '_VARS_PRINTED';

if (!defined($inlineGuard)) {
    define($inlineGuard, true);

    $inlineJs = sprintf(
        '(function(add){'
            . 'var w=window;'
            . 'var existing=w.BOOKANDO_VARS||{};'
            . 'if(existing.lang!=null){delete add.lang;}'
            . 'if(existing.wp_locale!=null){delete add.wp_locale;}'
            . 'var keys=Object.keys(add);'
            . 'for(var i=0;i<keys.length;i++){' 
                . 'var key=keys[i];'
                . 'if(!Object.prototype.hasOwnProperty.call(existing,key)||existing[key]==null){'
                    . 'existing[key]=add[key];'
                . '}'
            . '}'
            . 'w.BOOKANDO_VARS=existing;'
        . '})(%s);',
        wp_json_encode($defaults)
    );

    wp_add_inline_script($handle, $inlineJs, 'before');
}

// ---------------------------------------------
// Vue-Mountpoint ausgeben
// ---------------------------------------------
$rootAttributes = [
    'id'           => "bookando-{$moduleSlug}-root",
    'data-module'  => $moduleSlug,
    'data-locale'  => $lang,
    'class'        => 'bookando-admin-page',
];

if (isset($moduleData['root_attributes']) && is_array($moduleData['root_attributes'])) {
    foreach ($moduleData['root_attributes'] as $attr => $value) {
        if ($value === null) {
            continue;
        }
        $attr = (string) $attr;
        if ($attr === 'class' && isset($rootAttributes['class']) && $rootAttributes['class'] !== '') {
            $rootAttributes['class'] = trim($rootAttributes['class'] . ' ' . (string) $value);
            continue;
        }
        $rootAttributes[$attr] = $value;
    }
}

$placeholder = isset($moduleData['placeholder']) && is_string($moduleData['placeholder'])
    ? $moduleData['placeholder']
    : __('Lade Modul...', 'bookando');

$attrString = '';
foreach ($rootAttributes as $attr => $value) {
    $attrString .= sprintf(' %s="%s"', esc_attr($attr), esc_attr((string) $value));
}

printf('<div%1$s>%2$s</div>', $attrString, esc_html($placeholder));
