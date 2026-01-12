<?php

declare(strict_types=1);

namespace Bookando\Tests\Unit\Modules\Resources;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Manager\ModuleManifest;
use PHPUnit\Framework\TestCase;

use function bookando_test_reset_stubs;

final class ModuleManifestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        LicenseManager::clear();
    }

    protected function tearDown(): void
    {
        LicenseManager::clear();

        parent::tearDown();
    }

    public function test_manifest_declares_rest_write_feature(): void
    {
        $manifest = new ModuleManifest('resources');
        $features = $manifest->getFeaturesRequired();

        $this->assertContains('rest_api_read', $features);
        $this->assertContains('rest_api_write', $features);
    }

    public function test_required_features_align_with_license_checks(): void
    {
        $baseFeatures = ['resource_management', 'rest_api_read'];

        LicenseManager::setLicenseData([
            'key'      => 'test',
            'modules'  => ['resources'],
            'features' => $baseFeatures,
            'plan'     => 'starter',
        ]);

        $this->assertFalse(
            LicenseManager::hasAllRequiredFeatures('resources'),
            'Missing rest_api_write should block resource writes in the UI layer.'
        );

        LicenseManager::setLicenseData([
            'key'      => 'test',
            'modules'  => ['resources'],
            'features' => array_merge($baseFeatures, ['rest_api_write']),
            'plan'     => 'starter',
        ]);

        $this->assertTrue(
            LicenseManager::hasAllRequiredFeatures('resources'),
            'Providing rest_api_write should unlock resource writes consistently.'
        );
    }
}

