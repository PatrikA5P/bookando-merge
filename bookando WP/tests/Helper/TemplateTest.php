<?php

declare(strict_types=1);

namespace Bookando\Tests\Helper;

use Bookando\Helper\Template;
use PHPUnit\Framework\TestCase;

final class TemplateTest extends TestCase
{
    private string $pluginDir;

    private string $themeDir;

    /** @var list<string> */
    private array $includedPaths = [];

    /** @var array<string, mixed> */
    private array $options = [];

    private mixed $filterValue = null;

    /** @var list<string> */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $baseDir = sys_get_temp_dir() . '/bookando-template-' . uniqid('', true);
        $this->pluginDir = $baseDir . '/plugin';
        $this->themeDir  = $baseDir . '/theme';

        mkdir($this->pluginDir . '/src/modules/sample/Templates', 0777, true);
        file_put_contents($this->pluginDir . '/src/modules/sample/Templates/admin-vue-container.php', '<?php // plugin template');

        mkdir($this->pluginDir . '/src/modules/sample/templates', 0777, true);
        file_put_contents($this->pluginDir . '/src/modules/sample/templates/legacy.php', '<?php // legacy template');

        mkdir($this->themeDir . '/bookando/sample', 0777, true);
        file_put_contents($this->themeDir . '/bookando/sample/theme.php', '<?php // theme template');

        $this->includedPaths = [];
        $this->options       = [];
        $this->filterValue   = null;
        $this->logs          = [];
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(dirname($this->pluginDir));

        parent::tearDown();
    }

    public function testShouldUseFallbackWhenOptionEnabled(): void
    {
        $this->options['bookando_fallback_mode'] = true;

        $template = $this->createTemplateHelper();

        self::assertTrue($template->shouldUseFallback());
    }

    public function testShouldUseFallbackWithSanitizedQueryParameter(): void
    {
        $this->filterValue = ' 1 ';

        $template = $this->createTemplateHelper();

        self::assertTrue($template->shouldUseFallback());
    }

    public function testShouldUseFallbackTreatsZeroAsFalse(): void
    {
        $this->filterValue = '0';

        $template = $this->createTemplateHelper();

        self::assertFalse($template->shouldUseFallback());
    }

    public function testRenderIncludesWhitelistedPluginTemplate(): void
    {
        $template = $this->createTemplateHelper();

        $template->render('../Sample', '../admin-vue-container');

        self::assertCount(1, $this->includedPaths);
        self::assertStringEndsWith('/src/modules/sample/Templates/admin-vue-container.php', $this->includedPaths[0]);
    }

    public function testRenderPrefersThemeTemplateWhenAvailable(): void
    {
        $template = $this->createTemplateHelper();

        $template->render('sample', 'theme');

        self::assertCount(1, $this->includedPaths);
        self::assertStringEndsWith('/bookando/sample/theme.php', $this->includedPaths[0]);
    }

    public function testRenderRejectsUnknownTemplate(): void
    {
        $template = $this->createTemplateHelper();

        $template->render('sample', 'does-not-exist');

        self::assertSame([], $this->includedPaths);
        self::assertNotEmpty($this->logs);
        self::assertStringContainsString('Template not whitelisted', $this->logs[0]);
    }

    private function createTemplateHelper(): Template
    {
        return new Template(
            $this->pluginDir,
            fn(): string => $this->themeDir,
            function (string $option, $default = false) {
                return $this->options[$option] ?? $default;
            },
            function (int $type, string $name, int $filter, mixed $options = null) {
                return $this->filterValue;
            },
            fn($value) => sanitize_text_field($value),
            fn($value) => sanitize_file_name($value),
            fn(string $path): bool => file_exists($path),
            function (string $path): void {
                $this->includedPaths[] = $path;
            },
            function (string $message): void {
                $this->logs[] = $message;
            }
        );
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}
