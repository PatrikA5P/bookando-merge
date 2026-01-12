<?php

declare(strict_types=1);

use Bookando\Core\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginHelpersBootstrapTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testBootMakesHelperFunctionsAvailable(): void
    {
        require_once dirname(__DIR__, 3) . '/stubs/wordpress.php';
        require_once dirname(__DIR__, 3) . '/tests/stubs/case-sensitive-file-system.php';

        if (!defined('BOOKANDO_TEST_FORCE_CASE_SENSITIVE')) {
            define('BOOKANDO_TEST_FORCE_CASE_SENSITIVE', true);
        }

        if (!defined('BOOKANDO_PLUGIN_FILE')) {
            define('BOOKANDO_PLUGIN_FILE', dirname(__DIR__, 3) . '/bookando.php');
        }

        if (!defined('BOOKANDO_PLUGIN_DIR')) {
            define('BOOKANDO_PLUGIN_DIR', dirname(__DIR__, 3) . '/');
        }

        $plugin = new Plugin();
        $plugin->boot();

        $this->assertTrue(
            function_exists('bookando_is_dev'),
            'Helper function bookando_is_dev() should be available after boot.'
        );
    }
}
