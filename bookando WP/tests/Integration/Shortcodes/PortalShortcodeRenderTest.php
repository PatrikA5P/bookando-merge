<?php

namespace {
    if (!defined('BOOKANDO_PLUGIN_FILE')) {
        define('BOOKANDO_PLUGIN_FILE', __DIR__ . '/../../..' . '/bookando.php');
    }

    if (!defined('BOOKANDO_PLUGIN_DIR')) {
        define('BOOKANDO_PLUGIN_DIR', dirname(BOOKANDO_PLUGIN_FILE) . '/');
    }
}

namespace Bookando\Tests\Integration\Shortcodes {

    use Bookando\Core\Plugin;
    use PHPUnit\Framework\TestCase;

    class PortalShortcodeRenderTest extends TestCase
    {
        private array $createdFiles = [];

        private array $createdDirectories = [];

        private string $distDir;

        protected function setUp(): void
        {
            parent::setUp();

            $this->createdFiles       = [];
            $this->createdDirectories = [];
            $this->distDir            = BOOKANDO_PLUGIN_DIR . 'dist/';

            bookando_test_reset_stubs();
            Plugin::reset_portal_asset_state();
        }

        protected function tearDown(): void
        {
            foreach ($this->createdFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            foreach (array_reverse($this->createdDirectories) as $dir) {
                if (is_dir($dir)) {
                    @rmdir($dir);
                }
            }

            delete_option('bookando_design_settings');

            Plugin::reset_portal_asset_state();
            bookando_test_reset_stubs();

            parent::tearDown();
        }

        public function test_render_booking_portal_uses_manifest_assets(): void
        {
            update_option('bookando_design_settings', ['primary_color' => '#fff']);

            $this->writeManifest([
                'frontend-booking/main.ts' => [
                    'isEntry' => true,
                    'file'    => 'frontend-booking/main-123.js',
                    'css'     => [
                        'frontend-booking/main-123.css',
                        'frontend-booking/extra-456.css',
                    ],
                ],
            ]);

            $this->createDistAsset('frontend-booking/main-123.js', 'console.log("booking");');
            $this->createDistAsset('frontend-booking/main-123.css', '/* css */');
            $this->createDistAsset('frontend-booking/extra-456.css', '/* extra */');

            $html = Plugin::render_booking_form_portal([]);

            $this->assertSame('<div id="bookando-booking-app" style="--bookando-primary_color: #fff;"></div>', $html);

            $this->assertArrayHasKey('bookando-booking-frontend', $GLOBALS['bookando_test_styles']);
            $style = $GLOBALS['bookando_test_styles']['bookando-booking-frontend'];
            $this->assertSame('https://example.test/wp-content/plugins/bookando/dist/frontend-booking/main-123.css', $style['src']);

            $this->assertArrayHasKey('bookando-booking-frontend-2', $GLOBALS['bookando_test_styles']);
            $secondStyle = $GLOBALS['bookando_test_styles']['bookando-booking-frontend-2'];
            $this->assertSame('https://example.test/wp-content/plugins/bookando/dist/frontend-booking/extra-456.css', $secondStyle['src']);

            $this->assertArrayHasKey('bookando-booking-frontend', $GLOBALS['bookando_test_scripts']);
            $script = $GLOBALS['bookando_test_scripts']['bookando-booking-frontend'];
            $this->assertSame('https://example.test/wp-content/plugins/bookando/dist/frontend-booking/main-123.js', $script['src']);
            $this->assertSame(['bookando-polyfills', 'bookando-portal-bridge'], $script['deps']);
            $this->assertTrue($script['in_footer']);
            $this->assertSame(['design' => ['primary_color' => '#fff']], $script['localized']['BOOKANDO_PORTAL_VARS'] ?? null);

            $this->assertArrayHasKey('bookando-portal-bridge', $GLOBALS['bookando_test_scripts']);
            $bridge = $GLOBALS['bookando_test_scripts']['bookando-portal-bridge'];
            $this->assertSame(['bookando-polyfills'], $bridge['deps']);
            $this->assertCount(1, $bridge['inline_before']);
            $this->assertArrayHasKey('wpApiSettings', $bridge['localized']);

            $this->assertArrayHasKey('bookando-polyfills', $GLOBALS['bookando_test_scripts']);
        }

        public function test_multiple_portals_share_bridge_without_duplicates(): void
        {
            update_option('bookando_design_settings', ['primary_color' => '#123456']);

            $this->writeManifest([
                'frontend-booking/main.ts' => [
                    'isEntry' => true,
                    'file'    => 'frontend-booking/main-123.js',
                    'css'     => ['frontend-booking/main-123.css'],
                ],
                'customer-portal/main.ts' => [
                    'isEntry' => true,
                    'file'    => 'customer-portal/main-abc.js',
                    'css'     => ['customer-portal/main-abc.css'],
                ],
            ]);

            $this->createDistAsset('frontend-booking/main-123.js', 'console.log("booking");');
            $this->createDistAsset('frontend-booking/main-123.css', '/* css */');
            $this->createDistAsset('customer-portal/main-abc.js', 'console.log("customer");');
            $this->createDistAsset('customer-portal/main-abc.css', '/* css */');

            $first  = Plugin::render_booking_form_portal([]);
            $second = Plugin::render_customer_portal([]);

            $this->assertSame('<div id="bookando-booking-app" style="--bookando-primary_color: #123456;"></div>', $first);
            $this->assertSame('<div id="bookando-customer-portal-app" style="--bookando-primary_color: #123456;"></div>', $second);

            $bridge = $GLOBALS['bookando_test_scripts']['bookando-portal-bridge'];
            $this->assertCount(1, $bridge['inline_before']);
            $this->assertTrue($bridge['enqueued']);

            $customerScript = $GLOBALS['bookando_test_scripts']['bookando-customer-frontend'];
            $this->assertSame(['bookando-polyfills', 'bookando-portal-bridge'], $customerScript['deps']);
            $this->assertArrayHasKey('BOOKANDO_PORTAL_VARS', $customerScript['localized']);

            $this->assertArrayHasKey('bookando-customer-frontend', $GLOBALS['bookando_test_styles']);
        }

        public function test_employee_portal_falls_back_when_manifest_missing(): void
        {
            update_option('bookando_design_settings', ['secondary_color' => '#00ff00']);

            $this->createDistAsset('employee-portal/main.js', 'console.log("employee");');
            $this->createDistAsset('employee-portal/main.css', '/* css */');

            $html = Plugin::render_employee_portal([]);

            $this->assertSame('<div id="bookando-employee-portal-app" style="--bookando-secondary_color: #00ff00;"></div>', $html);

            $this->assertArrayHasKey('bookando-employee-frontend', $GLOBALS['bookando_test_styles']);
            $style = $GLOBALS['bookando_test_styles']['bookando-employee-frontend'];
            $this->assertSame('https://example.test/wp-content/plugins/bookando/dist/employee-portal/main.css', $style['src']);

            $this->assertArrayHasKey('bookando-employee-frontend', $GLOBALS['bookando_test_scripts']);
            $script = $GLOBALS['bookando_test_scripts']['bookando-employee-frontend'];
            $this->assertSame('https://example.test/wp-content/plugins/bookando/dist/employee-portal/main.js', $script['src']);
        }

        private function writeManifest(array $entries): void
        {
            $this->ensureDirectory($this->distDir);
            $manifestDir = $this->distDir . '.vite';
            $this->ensureDirectory($manifestDir);

            $path = $manifestDir . '/manifest.json';
            file_put_contents($path, json_encode($entries, JSON_PRETTY_PRINT));
            $this->createdFiles[] = $path;
        }

        private function createDistAsset(string $relativePath, string $contents): void
        {
            $this->ensureDirectory($this->distDir);

            $fullPath = $this->distDir . ltrim($relativePath, '/');
            $this->ensureDirectory(dirname($fullPath));

            file_put_contents($fullPath, $contents);
            $this->createdFiles[] = $fullPath;
        }

        private function ensureDirectory(string $path): void
        {
            if (is_dir($path)) {
                return;
            }

            mkdir($path, 0777, true);
            $this->createdDirectories[] = $path;
        }
    }
}
