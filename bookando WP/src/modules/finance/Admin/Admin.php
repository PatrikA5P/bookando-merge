<?php

declare(strict_types=1);

namespace Bookando\Modules\finance\Admin;

use Bookando\Core\Base\BaseAdmin;
use function __;

class Admin extends BaseAdmin
{
    protected static function getPageTitle(): string  { return __('Finance', 'bookando'); }
    protected static function getMenuSlug(): string   { return 'bookando_finance'; }
    protected static function getCapability(): string { return 'manage_bookando_finance'; }
    protected static function getTemplate(): string   { return 'admin-vue-container'; }
    protected static function getModuleSlug(): string { return 'finance'; }
    protected static function getMenuIcon(): string   { return 'dashicons-chart-area'; }
    protected static function getMenuPosition(): int  { return 30; }

}
