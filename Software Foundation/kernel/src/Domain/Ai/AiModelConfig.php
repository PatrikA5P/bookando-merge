<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Ai;

use InvalidArgumentException;

final class AiModelConfig
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly int $maxTokens = 4096,
        public readonly float $temperature = 0.7,
    ) {
        if ($this->temperature < 0.0 || $this->temperature > 2.0) {
            throw new InvalidArgumentException(
                sprintf('Temperature must be between 0.0 and 2.0, got %f.', $this->temperature)
            );
        }

        if ($this->maxTokens <= 0) {
            throw new InvalidArgumentException(
                sprintf('maxTokens must be greater than 0, got %d.', $this->maxTokens)
            );
        }
    }

    public static function geminiFlash(): self
    {
        return new self(
            provider: 'gemini',
            model: 'gemini-2.0-flash',
            maxTokens: 4096,
            temperature: 0.7,
        );
    }

    public static function geminiPro(): self
    {
        return new self(
            provider: 'gemini',
            model: 'gemini-2.0-pro',
            maxTokens: 8192,
            temperature: 0.3,
        );
    }

    /**
     * @return array{provider: string, model: string, maxTokens: int, temperature: float}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'maxTokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];
    }
}
