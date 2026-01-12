<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ModuleTemplateDirectoryCaseTest extends TestCase
{
    public function testModulesUsePascalCaseTemplatesDirectory(): void
    {
        $modulesDir = realpath(__DIR__ . '/../../src/modules');
        $this->assertNotFalse($modulesDir, 'Modules directory not found');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }

            $name = $fileInfo->getFilename();

            if ($name === 'Templates') {
                continue;
            }

            if (strtolower($name) === 'templates') {
                $violations[] = $fileInfo->getPathname();
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Found directories with legacy casing 'templates':\n" . implode("\n", $violations)
        );
    }
}
