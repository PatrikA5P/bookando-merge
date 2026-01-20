<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers\Admin;

use Bookando\Core\Base\BaseAdmin;
use Bookando\Modules\Offers\OfferType;
use function __;

class Admin extends BaseAdmin
{
    protected static function getPageTitle(): string  { return __('Offers', 'bookando'); }
    protected static function getMenuSlug(): string   { return 'bookando_offers'; }
    protected static function getCapability(): string { return 'manage_bookando_offers'; }
    protected static function getTemplate(): string   { return 'admin-vue-container'; }
    protected static function getModuleSlug(): string { return 'offers'; }
    protected static function getMenuIcon(): string   { return 'dashicons-products'; }
    protected static function getMenuPosition(): int  { return 26; }

    /**
     * Register three separate menu pages for each offer type
     * NO "All Offers" tab - strict separation by type
     */
    public static function register_menu(): void
    {
        // Main menu: Termine (Individual Appointments)
        \Bookando\Core\Admin\Menu::addModuleSubmenu([
            'page_title'  => OfferType::getLabel(OfferType::TERMINE),
            'menu_title'  => OfferType::getLabel(OfferType::TERMINE),
            'capability'  => static::getCapability(),
            'menu_slug'   => 'bookando_offers_termine',
            'module_slug' => static::getModuleSlug(),
            'callback'    => [static::class, 'renderTerminePage'],
            'icon_url'    => static::getMenuIcon(),
            'position'    => static::getMenuPosition(),
        ]);

        // Submenu: Kurse (Planned Courses)
        add_submenu_page(
            'bookando_offers_termine',
            OfferType::getLabel(OfferType::KURSE),
            OfferType::getLabel(OfferType::KURSE),
            static::getCapability(),
            'bookando_offers_kurse',
            [static::class, 'renderKursePage']
        );

        // Submenu: Online (E-Learning)
        add_submenu_page(
            'bookando_offers_termine',
            OfferType::getLabel(OfferType::ONLINE),
            OfferType::getLabel(OfferType::ONLINE),
            static::getCapability(),
            'bookando_offers_online',
            [static::class, 'renderOnlinePage']
        );
    }

    public static function renderTerminePage(): void
    {
        self::renderTypePage(OfferType::TERMINE);
    }

    public static function renderKursePage(): void
    {
        self::renderTypePage(OfferType::KURSE);
    }

    public static function renderOnlinePage(): void
    {
        self::renderTypePage(OfferType::ONLINE);
    }

    /**
     * Render page with offer type context
     */
    protected static function renderTypePage(string $offerType): void
    {
        // Pass offer type to template
        $pageContext = [
            'offer_type' => $offerType,
            'offer_type_label' => OfferType::getLabel($offerType),
            'offer_type_description' => OfferType::getDescription($offerType),
        ];

        // Make context available to template
        extract($pageContext);

        include __DIR__ . '/../Templates/admin-vue-container.php';
    }
}
