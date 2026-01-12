<?php
declare(strict_types=1);

namespace Bookando\Modules\appointments;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\appointments\Admin\Admin;
use Bookando\Modules\appointments\Api\Api;
use Bookando\Modules\appointments\Capabilities;

class Module extends BaseModule
{
    public function register(): void
    {
        $this->registerCapabilities(Capabilities::class);

        $this->registerAdminHooks(function (): void {
            add_action('bookando_register_module_menus', [Admin::class, 'register_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        });

        Api::register();
        $this->registerRestRoutes([Api::class, 'registerRoutes']);
    }

    public function enqueue_admin_assets(): void
    {
        $this->enqueue_module_assets();
    }
}
