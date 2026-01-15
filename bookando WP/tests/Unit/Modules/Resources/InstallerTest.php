<?php

namespace Bookando\Tests\Unit\Modules\Resources;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Resources\Installer;
use Bookando\Modules\Resources\StateRepository;
use PHPUnit\Framework\TestCase;

final class InstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        TenantManager::reset();
        StateRepository::resetCache();
    }

    public function test_installer_seeds_defaults_for_current_tenant(): void
    {
        TenantManager::setCurrentTenantId(1);

        Installer::install();

        $option = get_option('bookando_resources_state_1');

        $this->assertIsArray($option);
        $this->assertArrayHasKey('locations', $option);
        $this->assertNotEmpty($option['locations']);
    }

    public function test_state_uses_seeded_identifiers_between_requests(): void
    {
        TenantManager::setCurrentTenantId(1);
        Installer::install();

        $first = StateRepository::getState();

        StateRepository::resetCache();
        $second = StateRepository::getState();

        $this->assertSame(
            $first['locations'][0]['id'],
            $second['locations'][0]['id'],
            'Seeded identifiers should not be regenerated on subsequent requests.'
        );
    }
}
