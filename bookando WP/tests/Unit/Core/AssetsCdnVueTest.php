<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Bookando\Core\Assets;
use PHPUnit\Framework\TestCase;

final class AssetsTestProxy extends Assets
{
    public static function shouldEnqueueCdnVue(): bool
    {
        return parent::should_enqueue_cdn_vue();
    }

    public static function resetCache(): void
    {
        self::$cdnManifestExternalized = null;
    }
}

final class AssetsCdnVueTest extends TestCase
{
    private string $distDir;

    /** @var list<string> */
    private array $createdFiles = [];

    /** @var list<string> */
    private array $createdDirectories = [];

    private bool $distExistedBefore = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->distDir = dirname(__DIR__, 3) . '/dist';
        $this->distExistedBefore = is_dir($this->distDir);
        if (!$this->distExistedBefore) {
            $this->ensureDirectory($this->distDir);
        }

        AssetsTestProxy::resetCache();
        putenv('VITE_USE_CDN=false');
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        usort($this->createdDirectories, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($this->createdDirectories as $dir) {
            if (is_dir($dir)) {
                @rmdir($dir);
            }
        }

        if (!$this->distExistedBefore && is_dir($this->distDir)) {
            @rmdir($this->distDir);
        }

        AssetsTestProxy::resetCache();

        parent::tearDown();
    }

    public function testShouldEnqueueCdnVueReadsViteManifest(): void
    {
        $this->writeManifest('.vite/manifest.json', '{"app.js":"https://cdn.jsdelivr.net/npm/vue@3.5.21/dist/vue.esm-browser.prod.js"}');

        self::assertTrue(AssetsTestProxy::shouldEnqueueCdnVue());
    }

    public function testShouldEnqueueCdnVueFallsBackToLegacyManifest(): void
    {
        $this->writeManifest('manifest.json', '{"app.js":"https://cdn.jsdelivr.net/npm/pinia@2.1.6/dist/pinia.esm-browser.prod.js"}');

        self::assertTrue(AssetsTestProxy::shouldEnqueueCdnVue());
    }

    private function writeManifest(string $relativePath, string $contents): void
    {
        $path = $this->distDir . '/' . ltrim($relativePath, '/');
        $this->ensureDirectory(dirname($path));

        file_put_contents($path, $contents);
        $this->createdFiles[] = $path;
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create directory: ' . $dir);
            }
            $this->createdDirectories[] = $dir;
        }
    }
}
