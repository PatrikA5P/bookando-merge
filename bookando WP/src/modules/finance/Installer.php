<?php
declare(strict_types=1);

namespace Bookando\Modules\finance;

final class Installer
{
    public static function install(): void
    {
        StateRepository::seedDefaults();
    }
}
