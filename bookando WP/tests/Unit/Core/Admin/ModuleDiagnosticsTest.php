<?php

use Bookando\Core\Admin\ModuleDiagnostics;
use PHPUnit\Framework\TestCase;

final class ModuleDiagnosticsTest extends TestCase
{
    /**
     * @dataProvider diffProvider
     */
    public function testDiffMissingSlugs(array $active, array $available, array $expected): void
    {
        self::assertSame($expected, ModuleDiagnostics::diffMissingSlugs($active, $available));
    }

    public static function diffProvider(): array
    {
        return [
            'basic difference' => [
                ['employees', 'workday', 'resources'],
                ['employees', 'resources'],
                ['workday'],
            ],
            'normalizes and removes duplicates' => [
                ['WorkDay', 'WorkDay', '  resources  ', 'tools'],
                ['resources', 'tools'],
                ['workday'],
            ],
            'ignores invalid entries' => [
                ['employees', null, ''],
                ['employees'],
                [],
            ],
        ];
    }
}
