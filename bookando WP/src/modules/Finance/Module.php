<?php
declare(strict_types=1);

namespace Bookando\Modules\Finance;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\Finance\Admin\Admin;
use Bookando\Modules\Finance\Api\Api;
use Bookando\Modules\Finance\Capabilities;
use Bookando\Modules\Finance\Installer;

class Module extends BaseModule
{
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

    public static function install(): void
    {
        Installer::install();
    }
}
