<?php

namespace Bookando\Core\Base;

use Bookando\Helper\Template;

/**
 * Zentrale Basisklasse für alle Modul-Admin-UIs
 */
abstract class BaseAdmin
{
    private static ?Template $templateHelper = null;

    public static function setTemplateHelper(Template $template): void
    {
        self::$templateHelper = $template;
    }

    // Muss von jedem Modul-Admin implementiert werden:
    abstract protected static function getPageTitle(): string;
    abstract protected static function getMenuSlug(): string;
    abstract protected static function getCapability(): string;
    abstract protected static function getTemplate(): string;
    abstract protected static function getModuleSlug(): string;
    abstract protected static function getMenuIcon(): string;
    abstract protected static function getMenuPosition(): int;

    /**
     * Registriert das Admin-Menü via zentrale Menü-Logik
     */
    public static function register_menu(): void
    {
        \Bookando\Core\Admin\Menu::addModuleSubmenu([
            'page_title'  => static::getPageTitle(),
            'menu_title'  => static::getPageTitle(),
            'capability'  => static::getCapability(),
            'menu_slug'   => static::getMenuSlug(),
            'callback'    => [static::class, 'renderPage'],
            'module_slug' => static::getModuleSlug(),
            'icon_url'    => static::getMenuIcon(),
            'position'    => static::getMenuPosition(),
        ]);
    }

    /**
     * Standard-Render: Nutzt das zentrale Bookando-Template-System.
     */
    public static function renderPage(): void
    {
        if (!current_user_can(static::getCapability())) {
            wp_die(__('Keine Berechtigung', 'bookando'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(static::getPageTitle()) . '</h1>';

        // Slug an Template übergeben:
        $slug = static::getModuleSlug();
        if (self::$templateHelper === null) {
            wp_die(__('Template service not available', 'bookando'));
        }

        self::$templateHelper->render($slug, static::getTemplate());

        echo '</div>';
    }
}
