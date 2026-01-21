<?php

declare(strict_types=1);

namespace Bookando\Modules\Dashboard\Admin;

class Admin
{
    public static function register_menu(): void
    {
        \Bookando\Core\Admin\Menu::addModuleSubmenu([
            'page_title'  => __('Dashboard', 'bookando'),
            'menu_title'  => __('Dashboard', 'bookando'),
            'capability'  => 'manage_bookando_dashboard',
            'menu_slug'   => 'bookando_dashboard',
            'module_slug' => 'dashboard',
            'callback'    => [self::class, 'renderPage'],
            'icon_url'    => 'dashicons-dashboard',
            'position'    => 5
        ]);
    }

    public static function renderPage(): void
    {
        ?>
        <div id="bookando-dashboard-root"></div>
        <?php
    }
}
