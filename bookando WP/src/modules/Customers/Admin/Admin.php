<?php

declare(strict_types=1);

namespace Bookando\Modules\Customers\Admin;

use Bookando\Core\Base\BaseAdmin;
use function __;

class Admin extends BaseAdmin
{
    protected static function getPageTitle(): string     { return __('Customers', 'bookando'); }
    protected static function getMenuSlug(): string      { return 'bookando_customers'; }
    protected static function getCapability(): string    { return 'manage_bookando_customers'; }
    protected static function getTemplate(): string      { return 'admin-vue-container'; }
    protected static function getModuleSlug(): string    { return 'customers'; }
    protected static function getMenuIcon(): string      { return 'dashicons-groups'; }
    protected static function getMenuPosition(): int     { return 30; }

    public static function register_menu(): void
    {
        \Bookando\Core\Admin\Menu::addModuleSubmenu([
            'page_title'  => static::getPageTitle(),
            'menu_title'  => static::getPageTitle(),
            'capability'  => static::getCapability(),
            'menu_slug'   => static::getMenuSlug(),
            'module_slug' => static::getModuleSlug(),
            'callback'    => [static::class, 'renderPage'],
            'icon_url'    => static::getMenuIcon(),
            'position'    => static::getMenuPosition()
        ]);
    }

    public static function renderPage(): void
    {
        include __DIR__ . '/../Templates/admin-vue-container.php';
    }
}