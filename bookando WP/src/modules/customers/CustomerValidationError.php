<?php
namespace Bookando\Modules\customers;

class CustomerValidationError
{
    private string $code;

    private string $message;

    private int $status;

    /** @var array<string, mixed> */
    private array $details;

    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $code, string $message, int $status, array $details = [])
    {
        $this->code    = $code;
        $this->message = $message;
        $this->status  = $status;
        $this->details = $details;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
