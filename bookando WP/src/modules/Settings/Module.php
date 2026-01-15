<?php
declare(strict_types=1);

namespace Bookando\Modules\Settings;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\Settings\Admin\Admin;
use Bookando\Modules\Settings\Api\Api;
use Bookando\Modules\Settings\Capabilities;

class Module extends BaseModule
{
    private const OPTION_KEY = 'bookando_module_installed_at_settings';
    private const LEGACY_OPTION_KEY = 'bookando_module_installed_at_customers';

    public static function install(): void
    {
        // später: Installer::install(); (wenn wirklich gebraucht)
        if (class_exists(Capabilities::class) && method_exists(Capabilities::class, 'register')) {
            Capabilities::register(); // idempotent
        }

        self::migrateInstalledOption();

        if (get_option(self::OPTION_KEY, null) === null) {
            update_option(self::OPTION_KEY, time(), false);
        }
    }

    public function register(): void
    {
        $this->registerCapabilities(Capabilities::class);

        $module = $this; // Capture $this for use in closure
        $this->registerAdminHooks(function () use ($module): void {
            add_action('bookando_register_module_menus', [Admin::class, 'register_menu']);
            add_action('admin_enqueue_scripts', [$module, 'enqueue_admin_assets']);
        });

        Api::register();
        $this->registerRestRoutes([Api::class, 'registerRoutes']);
    }

    public function enqueue_admin_assets(): void
    {
        $this->enqueue_module_assets();
    }

    private static function migrateInstalledOption(): void
    {
        $legacyValue = get_option(self::LEGACY_OPTION_KEY, null);
        if ($legacyValue === null) {
            return;
        }

        if (get_option(self::OPTION_KEY, null) === null) {
            update_option(self::OPTION_KEY, $legacyValue, false);
        }

        delete_option(self::LEGACY_OPTION_KEY);
    }
}

