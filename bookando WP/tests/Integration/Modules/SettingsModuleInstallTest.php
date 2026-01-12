<?php

namespace Bookando\Tests\Integration\Modules;

use Bookando\Modules\settings\Module;

class SettingsModuleInstallTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        delete_option('bookando_module_installed_at_settings');
        delete_option('bookando_module_installed_at_customers');

        parent::tearDown();
    }

    public function test_install_records_timestamp_under_settings_key(): void
    {
        delete_option('bookando_module_installed_at_settings');
        delete_option('bookando_module_installed_at_customers');

        Module::install();

        $value = get_option('bookando_module_installed_at_settings', null);

        $this->assertNotNull($value);
        $this->assertTrue(is_numeric($value));
        $this->assertNull(get_option('bookando_module_installed_at_customers', null));
    }

    public function test_install_migrates_legacy_option(): void
    {
        delete_option('bookando_module_installed_at_settings');
        update_option('bookando_module_installed_at_customers', 12345, false);

        Module::install();

        $this->assertSame(12345, (int) get_option('bookando_module_installed_at_settings', 0));
        $this->assertNull(get_option('bookando_module_installed_at_customers', null));
    }
}
