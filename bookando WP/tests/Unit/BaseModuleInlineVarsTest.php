<?php

namespace {
    if (!defined('BOOKANDO_PLUGIN_FILE')) {
        define('BOOKANDO_PLUGIN_FILE', __DIR__ . '/../../bookando.php');
    }

    if (!function_exists('admin_url')) {
        function admin_url(string $path = ''): string
        {
            $path = ltrim($path, '/');
            return 'https://example.test/wp-admin/' . $path;
        }
    }

    if (!function_exists('rest_url')) {
        function rest_url(string $path = ''): string
        {
            $path = ltrim($path, '/');
            return 'https://example.test/wp-json/' . $path;
        }
    }

    if (!function_exists('plugins_url')) {
        function plugins_url(string $path = '', string $plugin = ''): string
        {
            $path = ltrim($path, '/');
            return 'https://example.test/wp-content/plugins/bookando/' . $path;
        }
    }

    if (!function_exists('trailingslashit')) {
        function trailingslashit(string $string): string
        {
            return rtrim($string, "/\\") . '/';
        }
    }
}

namespace Bookando\Tests\Unit {

    use Bookando\Core\Base\BaseModule;
    use Bookando\Core\Manager\ModuleManifest;
    use PHPUnit\Framework\TestCase;

    class BaseModuleInlineVarsTest extends TestCase
    {
        public function test_inline_vars_use_manifest_values(): void
        {
            $module = $this->createModule(true);

            $manifest = $this->createMock(ModuleManifest::class);
            $manifest->method('getPlan')->willReturn('enterprise');
            $manifest->method('getFeaturesRequired')->willReturn(['feature_a', 'feature_b']);
            $manifest->method('getTabs')->willReturn(['overview', 'settings']);

            $vars = $module->exposeBuildModuleVars('demo', $manifest);

            $this->assertSame('enterprise', $vars['required_plan']);
            $this->assertSame(['feature_a', 'feature_b'], $vars['features_required']);
            $this->assertSame(['overview', 'settings'], $vars['tabs']);
        }

        public function test_inline_vars_keep_fallback_when_manifest_missing_entries(): void
        {
            $module = $this->createModule(false);

            $manifest = $this->createMock(ModuleManifest::class);
            $manifest->method('getPlan')->willReturn('');
            $manifest->method('getFeaturesRequired')->willReturn([]);
            $manifest->method('getTabs')->willReturn([]);

            $vars = $module->exposeBuildModuleVars('demo', $manifest);

            $this->assertSame('starter', $vars['required_plan']);
            $this->assertSame(['export_csv'], $vars['features_required']);
            $this->assertSame([], $vars['tabs']);
            $this->assertFalse($vars['module_allowed']);
        }

        private function createModule(bool $allowed): BaseModule
        {
            return new class($allowed) extends BaseModule {
                public function __construct(private bool $allowedOverride)
                {
                }

                public function register(): void
                {
                    // no-op for tests
                }

                public function exposeBuildModuleVars(string $slug, ?ModuleManifest $manifest): array
                {
                    return $this->buildModuleVars($slug, $manifest);
                }

                protected function isModuleAllowed(string $slug): bool
                {
                    return $this->allowedOverride;
                }
            };
        }
    }
}
