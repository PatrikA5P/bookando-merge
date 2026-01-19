<?php
declare(strict_types=1);

namespace Bookando\Modules\DesignSystem;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\DesignSystem\Admin\Admin;

class Module extends BaseModule
{
    public function register(): void
    {
        $this->registerCapabilities(Capabilities::class);

        $module = $this;
        $this->registerAdminHooks(function () use ($module): void {
            add_action('bookando_register_module_menus', [Admin::class, 'register_menu']);
            add_action('admin_enqueue_scripts', [$module, 'enqueue_admin_assets']);
        });
    }

    public function enqueue_admin_assets(): void
    {
        $this->enqueue_module_assets();
    }
}
