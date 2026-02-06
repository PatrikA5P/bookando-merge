<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\I18n;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\I18n\Locale;

final class LocaleTest extends TestCase
{
    // --- Construction ---

    public function test_creates_from_tag_with_region(): void
    {
        $locale = Locale::of('de-CH');

        $this->assertSame('de', $locale->language);
        $this->assertSame('CH', $locale->region);
    }

    public function test_creates_from_tag_without_region(): void
    {
        $locale = Locale::of('de');

        $this->assertSame('de', $locale->language);
        $this->assertNull($locale->region);
    }

    // --- Named constructors ---

    public function test_named_constructors(): void
    {
        $german = Locale::german();
        $this->assertSame('de', $german->language);
        $this->assertNull($german->region);

        $germanCH = Locale::germanCH();
        $this->assertSame('de', $germanCH->language);
        $this->assertSame('CH', $germanCH->region);

        $english = Locale::english();
        $this->assertSame('en', $english->language);
        $this->assertNull($english->region);

        $french = Locale::french();
        $this->assertSame('fr', $french->language);
        $this->assertNull($french->region);

        $italian = Locale::italian();
        $this->assertSame('it', $italian->language);
        $this->assertNull($italian->region);
    }

    // --- Tag output ---

    public function test_tag_with_region(): void
    {
        $locale = Locale::of('de-CH');
        $this->assertSame('de-CH', $locale->tag());
    }

    public function test_tag_without_region(): void
    {
        $locale = Locale::of('de');
        $this->assertSame('de', $locale->tag());
    }

    // --- Validation ---

    public function test_rejects_invalid_language(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Locale('deu', null); // too long
    }

    public function test_rejects_uppercase_language(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Locale('DE', null); // must be lowercase
    }

    public function test_rejects_invalid_region(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Locale('de', 'ch'); // must be uppercase
    }

    // --- Comparison ---

    public function test_equals(): void
    {
        $a = Locale::of('de-CH');
        $b = Locale::of('de-CH');
        $c = Locale::of('de');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    // --- String representation ---

    public function test_to_string(): void
    {
        $this->assertSame('de-CH', (string) Locale::of('de-CH'));
        $this->assertSame('de', (string) Locale::of('de'));
    }
}
