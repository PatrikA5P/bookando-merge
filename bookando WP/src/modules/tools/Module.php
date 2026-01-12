<?php

declare(strict_types=1);

namespace Bookando\Modules\tools;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\tools\Admin\Admin;
use Bookando\Modules\tools\Api\Api;
use Bookando\Modules\tools\Capabilities;

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
