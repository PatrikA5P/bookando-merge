<?php
declare(strict_types=1);

namespace Bookando\Modules\Customers;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\Customers\Admin\Admin;
use Bookando\Modules\Customers\Api\Api;
use Bookando\Modules\Customers\Capabilities;

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
}
