<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Ai\AiModelConfig;

final class AiModelConfigTest extends TestCase
{
    // --- Named constructors ---

    public function test_gemini_flash(): void
    {
        $config = AiModelConfig::geminiFlash();

        $this->assertSame('gemini', $config->provider);
        $this->assertSame('gemini-2.0-flash', $config->model);
        $this->assertSame(4096, $config->maxTokens);
        $this->assertSame(0.7, $config->temperature);
    }

    public function test_gemini_pro(): void
    {
        $config = AiModelConfig::geminiPro();

        $this->assertSame('gemini', $config->provider);
        $this->assertSame('gemini-2.0-pro', $config->model);
        $this->assertSame(8192, $config->maxTokens);
        $this->assertSame(0.3, $config->temperature);
    }

    // --- Validation ---

    public function test_rejects_negative_temperature(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AiModelConfig(
            provider: 'gemini',
            model: 'test-model',
            temperature: -0.1,
        );
    }

    public function test_rejects_temperature_above_two(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AiModelConfig(
            provider: 'gemini',
            model: 'test-model',
            temperature: 2.1,
        );
    }

    public function test_rejects_zero_max_tokens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AiModelConfig(
            provider: 'gemini',
            model: 'test-model',
            maxTokens: 0,
        );
    }

    public function test_rejects_negative_max_tokens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AiModelConfig(
            provider: 'gemini',
            model: 'test-model',
            maxTokens: -100,
        );
    }

    // --- Serialization ---

    public function test_to_array(): void
    {
        $config = AiModelConfig::geminiFlash();
        $arr = $config->toArray();

        $this->assertSame('gemini', $arr['provider']);
        $this->assertSame('gemini-2.0-flash', $arr['model']);
        $this->assertSame(4096, $arr['maxTokens']);
        $this->assertSame(0.7, $arr['temperature']);
    }
}
