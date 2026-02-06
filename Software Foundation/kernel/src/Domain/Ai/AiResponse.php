<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Ai;

final class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly string $finishReason,
    ) {
    }

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    /**
     * @return array{content: string, model: string, inputTokens: int, outputTokens: int, finishReason: string, totalTokens: int}
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'inputTokens' => $this->inputTokens,
            'outputTokens' => $this->outputTokens,
            'finishReason' => $this->finishReason,
            'totalTokens' => $this->totalTokens(),
        ];
    }
}
