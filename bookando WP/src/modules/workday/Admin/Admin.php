<?php

declare(strict_types=1);

namespace Bookando\Modules\workday\Admin;

use Bookando\Core\Base\BaseAdmin;
use function __;

class Admin extends BaseAdmin
{
    protected static function getPageTitle(): string     { return __('Arbeitstag', 'bookando'); }
    protected static function getMenuSlug(): string      { return 'bookando_workday'; }
    protected static function getCapability(): string    { return 'manage_bookando_workday'; }
    protected static function getTemplate(): string      { return 'admin-vue-container'; }
    protected static function getModuleSlug(): string    { return 'workday'; }
    protected static function getMenuIcon(): string      { return 'dashicons-calendar-alt'; }
    protected static function getMenuPosition(): int     { return 50; }

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
