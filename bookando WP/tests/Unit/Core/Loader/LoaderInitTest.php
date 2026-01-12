<?php

declare(strict_types=1);

use Bookando\Core\Loader;
use Bookando\Core\Manager\ModuleManager;
use PHPUnit\Framework\TestCase;

final class LoaderInitTest extends TestCase
{
    public function testInitDoesNotThrowError(): void
    {
        require_once dirname(__DIR__, 4) . '/stubs/wordpress.php';
        require_once dirname(__DIR__, 4) . '/tests/stubs/wp-functions.php';

        if (!defined('BOOKANDO_PLUGIN_FILE')) {
            define('BOOKANDO_PLUGIN_FILE', dirname(__DIR__, 4) . '/bookando.php');
        }

        if (!defined('BOOKANDO_PLUGIN_DIR')) {
            define('BOOKANDO_PLUGIN_DIR', dirname(__DIR__, 4) . '/');
        }

        if (!function_exists('bookando_test_reset_stubs')) {
            $this->fail('Missing WordPress stub helpers.');
        }

        bookando_test_reset_stubs();

        global $wpdb;
        $wpdb = new Bookando_Test_SpyWpdb();

        $moduleManagerReflection = new \ReflectionClass(ModuleManager::class);
        $instanceProperty = $moduleManagerReflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);

        $loader = new Loader();

        try {
            $loader->init();
        } catch (\Error $error) {
            $this->fail('Loader::init() should not throw an Error. Message: ' . $error->getMessage());
        }

        $this->addToAssertionCount(1);
    }
}
