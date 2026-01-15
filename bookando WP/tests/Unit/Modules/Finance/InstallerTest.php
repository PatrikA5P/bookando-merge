<?php

namespace Bookando\Tests\Unit\Modules\Finance;

use Bookando\Modules\Finance\Installer;
use Bookando\Modules\Finance\StateRepository;
use PHPUnit\Framework\TestCase;

final class InstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        StateRepository::resetCache();
    }

    public function test_installer_seeds_finance_defaults(): void
    {
        Installer::install();

        $option = get_option('bookando_finance_state');

        $this->assertIsArray($option);
        $this->assertArrayHasKey('invoices', $option);
        $this->assertNotEmpty($option['invoices']);
    }

    public function test_seeded_finance_state_persists_between_requests(): void
    {
        Installer::install();

        $first = StateRepository::getState();

        StateRepository::resetCache();
        $second = StateRepository::getState();

        $this->assertSame(
            $first['invoices'][0]['id'],
            $second['invoices'][0]['id'],
            'Finance invoices should preserve their seeded identifiers.'
        );

        $this->assertSame(
            $first['discount_codes'][0]['code'],
            $second['discount_codes'][0]['code'],
            'Finance discount codes should persist across requests.'
        );
    }
}
