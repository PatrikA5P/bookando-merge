<?php
namespace Bookando\Modules\Customers;

class CustomerValidationResult
{
    /** @var array<string, mixed> */
    private array $data;

    private ?CustomerValidationError $error;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data, ?CustomerValidationError $error)
    {
        $this->data  = $data;
        $this->error = $error;
    }

    public function isValid(): bool
    {
        return $this->error === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function error(): ?CustomerValidationError
    {
        return $this->error;
    }
}
