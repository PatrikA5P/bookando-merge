<?php
declare(strict_types=1);

namespace Bookando\Modules\Academy;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\Academy\Admin\Admin;
use Bookando\Modules\Academy\Api\Api;
use Bookando\Modules\Academy\Capabilities;
use Bookando\Modules\Academy\AdminTemplateCreator;
use Bookando\Modules\Academy\AdminResetPage;

class Module extends BaseModule
{
    public function register(): void
    {
        $this->registerCapabilities(Capabilities::class);

        $this->registerAdminHooks(function (): void {
            add_action('bookando_register_module_menus', [Admin::class, 'register_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

            // Initialize admin tools
            AdminTemplateCreator::init();
            AdminResetPage::init();
        });

        Api::register();
        $this->registerRestRoutes([Api::class, 'registerRoutes']);
    }

    /**
     * Wird bei Plugin-Aktivierung aufgerufen.
     */
    public static function install(): void
    {
        Installer::install();
    }

    public function enqueue_admin_assets(): void
    {
        $this->enqueue_module_assets();
    }
}
