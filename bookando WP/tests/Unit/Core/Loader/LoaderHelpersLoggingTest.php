<?php

declare(strict_types=1);

use Bookando\Core\Loader;
use Bookando\Core\Service\ActivityLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LoaderHelpersLoggingTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testInitHelpersDoesNotLogWhenDebugDisabled(): void
    {
        require_once dirname(__DIR__, 4) . '/stubs/wordpress.php';
        require_once dirname(__DIR__, 4) . '/tests/stubs/wp-functions.php';

        if (!defined('BOOKANDO_PLUGIN_FILE')) {
            define('BOOKANDO_PLUGIN_FILE', dirname(__DIR__, 4) . '/bookando.php');
        }

        if (!defined('BOOKANDO_PLUGIN_DIR')) {
            define('BOOKANDO_PLUGIN_DIR', dirname(__DIR__, 4) . '/');
        }

        if (!defined('BOOKANDO_DEV')) {
            define('BOOKANDO_DEV', false);
        }

        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', false);
        }

        bookando_test_reset_stubs();

        global $wpdb;
        $wpdb = new Bookando_Test_SpyWpdb();
        $wpdb->registerLookup('wp_bookando_activity_log', 'wp_bookando_activity_log');

        $reflection = new ReflectionClass(ActivityLogger::class);
        $property   = $reflection->getProperty('tableExists');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $loader = new class extends Loader {
            public function exposeInitHelpers(): void
            {
                $this->initHelpers();
            }
        };

        $loader->exposeInitHelpers();

        $this->assertSame(
            [],
            $wpdb->inserted,
            'ActivityLogger::info should not be called when debug logging is disabled.'
        );
    }
}
