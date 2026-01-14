<?php

declare(strict_types=1);

namespace Bookando\Modules\Settings\Admin;

use Bookando\Core\Base\BaseAdmin;
use function __;

class Admin extends BaseAdmin
{
    protected static function getPageTitle(): string     { return __('Settings', 'bookando'); }
    protected static function getMenuSlug(): string      { return 'bookando_settings'; }
    protected static function getCapability(): string    { return 'manage_bookando_settings'; }
    protected static function getTemplate(): string      { return 'admin-vue-container'; }
    protected static function getModuleSlug(): string    { return 'settings'; }
    protected static function getMenuIcon(): string      { return 'dashicons-admin-generic'; }
    protected static function getMenuPosition(): int     { return 30; }
}
