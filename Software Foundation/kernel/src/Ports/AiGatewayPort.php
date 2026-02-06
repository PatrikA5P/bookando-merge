<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Ai\AiMessage;
use SoftwareFoundation\Kernel\Domain\Ai\AiModelConfig;
use SoftwareFoundation\Kernel\Domain\Ai\AiResponse;

interface AiGatewayPort
{
    /**
     * Send a conversation to an AI model and get a completion response.
     *
     * @param AiMessage[]   $messages  Ordered list of conversation messages
     * @param AiModelConfig $config    Model configuration
     * @param int           $tenantId  Tenant identifier for billing/tracking
     */
    public function complete(array $messages, AiModelConfig $config, int $tenantId): AiResponse;

    /**
     * Generate an embedding vector for the given text.
     *
     * @param string $text     Text to embed
     * @param int    $tenantId Tenant identifier for billing/tracking
     * @return float[] Embedding vector
     */
    public function embed(string $text, int $tenantId): array;

    /**
     * Moderate content for policy compliance.
     *
     * @param string $content  Content to moderate
     * @param int    $tenantId Tenant identifier for billing/tracking
     * @return array{flagged: bool, categories: array<string, bool>}
     */
    public function moderate(string $content, int $tenantId): array;
}
