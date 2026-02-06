<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Ai;

use InvalidArgumentException;

final class AiMessage
{
    private const VALID_ROLES = ['system', 'user', 'assistant'];

    public function __construct(
        public readonly string $role,
        public readonly string $content,
    ) {
        if (!in_array($this->role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Role must be one of [%s], got "%s".',
                    implode(', ', self::VALID_ROLES),
                    $this->role,
                )
            );
        }
    }

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
