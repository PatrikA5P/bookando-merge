<?php
namespace Bookando\Core;

use Bookando\Core\Helper\Manifest;

class Assets
{
    const VUE_VERSION   = '3.5.21';
    const CDN_VUE_ESM   = 'https://cdn.jsdelivr.net/npm/vue@3.5.21/dist/vue.esm-browser.prod.js';
    const CDN_VUE_IIFE  = 'https://unpkg.com/vue@3.5.21/dist/vue.global.prod.js';

    protected static ?bool $cdnManifestExternalized = null;

    /**
     * Lädt Vue nur dann von einem CDN, wenn es wirklich benötigt wird.
     * Primär über Vite gebundled – CDN lediglich für externe Builds.
     */
    public static function enqueue_vue_pinia() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || strpos($screen->id, 'bookando') === false) {
            return;
        }

        if (!self::should_enqueue_cdn_vue()) {
            return;
        }

        if (function_exists('wp_enqueue_script_module')) {
            if (!wp_script_is('bookando-cdn-vue', 'enqueued')) {
                wp_enqueue_script_module(
                    'bookando-cdn-vue',
                    self::CDN_VUE_ESM,
                    [],
                    null,
                    true
                );
            }
            return;
        }

        if (!wp_script_is('bookando-vue', 'enqueued')) {
            wp_enqueue_script(
                'bookando-vue',
                self::CDN_VUE_IIFE,
                [],
                self::VUE_VERSION,
                false
            );
        }
    }

    protected static function should_enqueue_cdn_vue(): bool
    {
        if (getenv('VITE_USE_CDN') === 'true') {
            return true;
        }

        if (self::$cdnManifestExternalized !== null) {
            return self::$cdnManifestExternalized;
        }

        $distDir   = dirname(__DIR__, 2) . '/dist/';
        $contents  = Manifest::loadRaw($distDir);
        if ($contents === null) {
            return self::$cdnManifestExternalized = false;
        }

        $needles = [
            'https://cdn.jsdelivr.net/npm/vue@',
            'https://cdn.jsdelivr.net/npm/pinia@',
            'https://cdn.jsdelivr.net/npm/vue-i18n@',
        ];

        foreach ($needles as $needle) {
            if (strpos($contents, $needle) !== false) {
                return self::$cdnManifestExternalized = true;
            }
        }

        return self::$cdnManifestExternalized = false;
    }
}
