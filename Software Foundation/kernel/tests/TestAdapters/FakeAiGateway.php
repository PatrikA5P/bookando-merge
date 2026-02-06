<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Ai\AiMessage;
use SoftwareFoundation\Kernel\Domain\Ai\AiModelConfig;
use SoftwareFoundation\Kernel\Domain\Ai\AiResponse;
use SoftwareFoundation\Kernel\Ports\AiGatewayPort;

/**
 * Fake AI gateway for testing. Returns pre-configured responses.
 */
final class FakeAiGateway implements AiGatewayPort
{
    /** @var string[] Queue of responses for complete() calls. */
    private array $responses = [];

    private int $completionCount = 0;

    /** @var float[] Fixed embedding vector returned by embed(). */
    private array $embeddingVector;

    /** @var array{flagged: bool, categories: array<string, bool>} */
    private array $moderationResult;

    public function __construct()
    {
        // Default 128-dimensional embedding vector of zeros.
        $this->embeddingVector = array_fill(0, 128, 0.0);
        $this->moderationResult = ['flagged' => false, 'categories' => []];
    }

    /** Set the next response content for complete(). Returns self for chaining. */
    public function withResponse(string $content): self
    {
        $this->responses[] = $content;
        return $this;
    }

    /** Set a custom embedding vector for embed(). */
    public function withEmbedding(array $vector): self
    {
        $this->embeddingVector = $vector;
        return $this;
    }

    /** Set a custom moderation result for moderate(). */
    public function withModerationResult(bool $flagged, array $categories = []): self
    {
        $this->moderationResult = ['flagged' => $flagged, 'categories' => $categories];
        return $this;
    }

    public function complete(array $messages, AiModelConfig $config, int $tenantId): AiResponse
    {
        $this->completionCount++;

        $content = $this->responses !== []
            ? array_shift($this->responses)
            : 'This is a fake AI response.';

        return new AiResponse(
            content: $content,
            model: $config->model,
            inputTokens: 10,
            outputTokens: 20,
            finishReason: 'stop',
        );
    }

    /**
     * @return float[]
     */
    public function embed(string $text, int $tenantId): array
    {
        return $this->embeddingVector;
    }

    /**
     * @return array{flagged: bool, categories: array<string, bool>}
     */
    public function moderate(string $content, int $tenantId): array
    {
        return $this->moderationResult;
    }

    // --- Test helpers ---

    /** Return how many times complete() has been called. */
    public function completionCount(): int
    {
        return $this->completionCount;
    }

    /** Reset all state: queued responses, counters, and defaults. */
    public function reset(): void
    {
        $this->responses = [];
        $this->completionCount = 0;
        $this->embeddingVector = array_fill(0, 128, 0.0);
        $this->moderationResult = ['flagged' => false, 'categories' => []];
    }
}
