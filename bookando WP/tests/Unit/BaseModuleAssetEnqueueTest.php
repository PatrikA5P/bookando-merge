<?php
declare(strict_types=1);

namespace Bookando\Tests\Unit;

use Bookando\Core\Base\BaseModule;
use PHPUnit\Framework\TestCase;

if (!defined('BOOKANDO_PLUGIN_FILE')) {
    define('BOOKANDO_PLUGIN_FILE', __DIR__ . '/../../bookando.php');
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        $path = ltrim($path, '/');
        return 'https://example.test/wp-content/plugins/bookando/' . $path;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        return dirname($file) . '/';
    }
}

/**
 * @covers \Bookando\Core\Base\BaseModule
 */
class BaseModuleAssetEnqueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        bookando_test_set_user_caps([]);
        bookando_test_set_current_screen(null);

        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    public function test_assets_are_not_enqueued_on_unrelated_screen(): void
    {
        bookando_test_set_user_caps(['manage_bookando_customers']);

        bookando_test_set_current_screen((object) ['id' => 'dashboard']);

        $module = $this->createModule();
        $module->enqueue_admin_assets();

        $this->assertSame([], $GLOBALS['bookando_test_enqueued_scripts']);
    }

    public function test_assets_require_capability(): void
    {
        $this->prepareValidScreenAndNonce();
        bookando_test_set_user_caps([]);

        $module = $this->createModule();
        $module->enqueue_admin_assets();

        $this->assertSame([], $GLOBALS['bookando_test_enqueued_scripts']);
    }

    public function test_assets_require_valid_nonce(): void
    {
        bookando_test_set_user_caps(['manage_bookando_customers']);
        bookando_test_set_current_screen((object) ['id' => 'bookando_page_bookando_customers']);

        $_GET['_wpnonce'] = 'nonce-bookando_module_assets_customers-invalid';
        $_REQUEST['_wpnonce'] = $_GET['_wpnonce'];

        $module = $this->createModule();
        $module->enqueue_admin_assets();

        $this->assertSame([], $GLOBALS['bookando_test_enqueued_scripts']);
    }

    public function test_assets_are_enqueued_with_valid_context(): void
    {
        $this->prepareValidScreenAndNonce();
        bookando_test_set_user_caps(['manage_bookando_customers']);

        $module = $this->createModule();
        $module->enqueue_admin_assets();

        $this->assertContains('bookando-customers-app', $GLOBALS['bookando_test_enqueued_scripts']);
    }

    private function prepareValidScreenAndNonce(): void
    {
        bookando_test_set_current_screen((object) ['id' => 'bookando_page_bookando_customers']);

        $nonce = 'nonce-bookando_module_assets_customers';
        $_GET['_wpnonce'] = $nonce;
        $_REQUEST['_wpnonce'] = $nonce;
        $_GET['page'] = 'bookando_customers';
        $_REQUEST['page'] = 'bookando_customers';
    }

    private function createModule(): BaseModule
    {
        return new class extends BaseModule {
            public function register(): void
            {
                // not required for tests
            }

            public function enqueue_admin_assets(): void
            {
                $this->enqueue_module_assets();
            }

            protected function loadModuleManifest(string $slug): ?\Bookando\Core\Manager\ModuleManifest
            {
                return null;
            }

            protected function collect_module_css_from_manifest(string $slug): array
            {
                return [];
            }

            protected function collect_module_css_legacy(string $slug): array
            {
                return [];
            }

            protected function buildModuleVars(string $slug, ?\Bookando\Core\Manager\ModuleManifest $manifest): array
            {
                return [
                    'module_allowed'    => true,
                    'required_plan'     => 'starter',
                    'features_required' => [],
                    'tabs'              => [],
                    'ajax_url'          => 'https://example.test/wp-admin/admin-ajax.php',
                    'rest_url'          => 'https://example.test/wp-json/bookando/v1/' . $slug,
                    'rest_url_base'     => 'https://example.test/wp-json/bookando/v1',
                    'rest_root'         => 'https://example.test/wp-json/',
                    'iconBase'          => 'https://example.test/wp-content/plugins/bookando/icons/',
                    'slug'              => $slug,
                ];
            }

            protected function getSlug(): string
            {
                return 'customers';
            }

            protected function isModuleAllowed(string $slug): bool
            {
                return true;
            }
        };
    }
}
