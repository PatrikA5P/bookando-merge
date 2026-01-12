<?php

declare(strict_types=1);

use Bookando\CLI\SeedDevLicenseCommand;
use PHPUnit\Framework\TestCase;

final class SeedDevLicenseCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        bookando_test_reset_stubs();
        WP_CLI::reset();
    }

    public function test_it_seeds_license_data_in_development_environment(): void
    {
        $GLOBALS['bookando_test_environment_type'] = 'development';
        $GLOBALS['bookando_test_user_caps']        = ['manage_options'];

        $command = new SeedDevLicenseCommand();
        $command->__invoke([], []);

        $data = get_option('bookando_license_data');

        $this->assertIsArray($data);
        $this->assertSame('dev-local-key', $data['key']);
        $this->assertSame('enterprise', $data['plan']);
        $this->assertSame('2025-01-01 12:00:00', $data['verified_at']);
        $this->assertNotEmpty($data['modules']);
        $this->assertNotEmpty($data['features']);

        $successMessages = array_filter(
            WP_CLI::$messages,
            static fn (array $entry): bool => $entry['type'] === 'success'
        );

        $this->assertCount(1, $successMessages);
    }

    public function test_it_requires_development_environment(): void
    {
        $GLOBALS['bookando_test_environment_type'] = 'production';
        $GLOBALS['bookando_test_user_caps']        = ['manage_options'];

        $command = new SeedDevLicenseCommand();

        $this->expectException(Bookando_Test_WpCliException::class);
        $this->expectExceptionMessage('Entwicklungsumgebung');

        $command->__invoke([], []);
    }

    public function test_it_requires_manage_options_capability(): void
    {
        $GLOBALS['bookando_test_environment_type'] = 'development';
        $GLOBALS['bookando_test_user_caps']        = [];

        $command = new SeedDevLicenseCommand();

        $this->expectException(Bookando_Test_WpCliException::class);
        $this->expectExceptionMessage('manage_options');

        $command->__invoke([], []);
    }

    public function test_it_warns_when_license_data_already_exists(): void
    {
        $GLOBALS['bookando_test_environment_type'] = 'development';
        $GLOBALS['bookando_test_user_caps']        = ['manage_options'];

        update_option('bookando_license_data', ['key' => 'existing']);

        $command = new SeedDevLicenseCommand();
        $command->__invoke([], []);

        $data = get_option('bookando_license_data');
        $this->assertSame(['key' => 'existing'], $data);

        $warningMessages = array_filter(
            WP_CLI::$messages,
            static fn (array $entry): bool => $entry['type'] === 'warning'
        );

        $this->assertCount(1, $warningMessages);
    }
}
