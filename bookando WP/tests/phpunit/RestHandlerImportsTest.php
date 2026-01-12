<?php

declare(strict_types=1);

namespace Bookando\Tests\PhpUnit;

use PHPUnit\Framework\TestCase;

final class RestHandlerImportsTest extends TestCase
{
    /**
     * @return array<int, array{string}>
     */
    public static function restHandlerProvider(): array
    {
        $modulesDir = dirname(__DIR__, 2) . '/src/modules';
        $files      = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modulesDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getFilename() !== 'RestHandler.php') {
                continue;
            }

            $files[] = [$fileInfo->getPathname()];
        }

        sort($files);

        return $files;
    }

    /**
     * @dataProvider restHandlerProvider
     */
    public function testRestHandlersImportGuardAndServer(string $file): void
    {
        require_once $file;

        $contents = file_get_contents($file);
        self::assertNotFalse($contents, sprintf('Datei %s konnte nicht gelesen werden.', $this->relativePath($file)));

        self::assertStringContainsString(
            'use Bookando\\Core\\Dispatcher\\RestModuleGuard;',
            $contents,
            sprintf('"%s" muss Bookando\\Core\\Dispatcher\\RestModuleGuard importieren.', $this->relativePath($file))
        );

        self::assertStringContainsString(
            'use WP_REST_Server;',
            $contents,
            sprintf('"%s" muss WP_REST_Server importieren.', $this->relativePath($file))
        );
    }

    private function relativePath(string $file): string
    {
        $base = dirname(__DIR__, 2) . '/';

        return str_starts_with($file, $base)
            ? substr($file, strlen($base))
            : $file;
    }
}
