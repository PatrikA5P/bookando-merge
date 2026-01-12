<?php

declare(strict_types=1);

use Bookando\Core\Loader;
use PHPUnit\Framework\TestCase;

final class LoaderHelpersCaseSensitivityTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testInitHelpersLoadsCapitalizedFile(): void
    {
        require_once dirname(__DIR__, 4) . '/stubs/wordpress.php';
        require_once dirname(__DIR__, 4) . '/tests/stubs/case-sensitive-file-system.php';

        if (!defined('BOOKANDO_TEST_FORCE_CASE_SENSITIVE')) {
            define('BOOKANDO_TEST_FORCE_CASE_SENSITIVE', true);
        }

        $this->assertFalse(function_exists('bookando_is_dev'));

        $loader = new class extends Loader {
            public function exposeInitHelpers(): void
            {
                $this->initHelpers();
            }
        };

        $loader->exposeInitHelpers();

        $this->assertTrue(
            function_exists('bookando_is_dev'),
            'Loader::initHelpers should load Helpers.php on case-sensitive filesystems.'
        );
    }
}
