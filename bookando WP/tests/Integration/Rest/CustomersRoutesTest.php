<?php
namespace Bookando\Tests\Integration\Rest;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WP_Error;
use Bookando\Modules\Customers\CustomerService;
use Bookando\Modules\Customers\CustomerValidationError;
use Bookando\Modules\Customers\CustomerValidationResult;
use Bookando\Modules\Customers\CustomerRepository;
use Bookando\Modules\Customers\CustomerValidator;

if (!class_exists('WP_Error')) {
    require_once __DIR__ . '/../../bootstrap.php';
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return (string) $text;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($value)
    {
        return preg_replace('/[^a-z0-9_]/', '', strtolower((string) $value));
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim((string) $value);
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'mysql' ? '2024-01-01 12:00:00' : '2024-01-01';
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value, $options = 0, $depth = 512)
    {
        return json_encode($value, $options, $depth);
    }
}

final class CustomersRoutesTest extends TestCase
{
    public function test_get_customer_returns_data(): void
    {
        $repository = new CustomerRepositoryFake();
        $repository->findReturn = ['id' => 5, 'status' => 'active'];

        $service = new CustomerService($repository, new CustomerValidatorFake());
        $result = $service->getCustomer(5, null);

        $this->assertIsArray($result);
        $this->assertSame(5, $result['id']);
        $this->assertSame([5, null], $repository->lastFindCall);
    }

    public function test_get_customer_handles_not_found(): void
    {
        $repository = new CustomerRepositoryFake();
        $repository->findReturn = null;

        $service = new CustomerService($repository, new CustomerValidatorFake());
        $result = $service->getCustomer(7, null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('not_found', $result->get_error_code());
    }

    public function test_get_customer_handles_hard_deleted(): void
    {
        $repository = new CustomerRepositoryFake();
        $repository->findReturn = ['id' => 3, 'status' => 'deleted', 'deleted_at' => '2023-01-01'];

        $service = new CustomerService($repository, new CustomerValidatorFake());
        $result = $service->getCustomer(3, null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('gone', $result->get_error_code());
    }

    public function test_list_customers_passes_filters(): void
    {
        $repository = new CustomerRepositoryFake();
        $repository->listReturn = ['data' => [], 'total' => 0, 'limit' => 10, 'offset' => 5];

        $service = new CustomerService($repository, new CustomerValidatorFake());
        $result = $service->listCustomers([
            'include_deleted' => 'all',
            'search'          => 'smith',
            'limit'           => 10,
            'offset'          => 5,
            'order'           => 'email',
            'dir'             => 'DESC',
        ], 4);

        $this->assertIsArray($result);
        $this->assertSame([], $result['data']);
        $this->assertSame('all', $repository->lastListFilters['include_deleted']);
        $this->assertSame(4, $repository->lastListTenantId);
    }

    public function test_create_customer_returns_validation_error(): void
    {
        $validator = new CustomerValidatorFake();
        $validator->createResult = new CustomerValidationResult([], new CustomerValidationError('invalid_email', 'invalid', 400));
        $service = new CustomerService(new CustomerRepositoryFake(), $validator);

        $result = $service->createCustomer(['email' => 'invalid'], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_email', $result->get_error_code());
    }

    public function test_create_customer_persists_data(): void
    {
        $repository = new CustomerRepositoryFake();
        $repository->nextInsertId = 42;
        $validator = new CustomerValidatorFake();
        $validator->createResult = new CustomerValidationResult(['email' => 'john@example.com'], null);

        $service = new CustomerService($repository, $validator);
        $result = $service->createCustomer(['email' => 'john@example.com'], 9);

        $this->assertIsArray($result);
        $this->assertSame(['customer'], json_decode($repository->lastInsert['roles'], true));
        $this->assertSame(9, $repository->lastInsert['tenant_id']);
        $this->assertSame(42, $result['id']);
    }

    public function test_update_customer_returns_validation_error(): void
    {
        $repository = new CustomerRepositoryFake();
        $validator = new CustomerValidatorFake();
        $validator->updateResult = new CustomerValidationResult([], new CustomerValidationError('validation_error', 'fail', 422, ['fields' => ['email']]));

        $service = new CustomerService($repository, $validator);
        $result = $service->updateCustomer(11, ['email' => ''], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_error', $result->get_error_code());
    }

    public function test_update_customer_updates_fields(): void
    {
        $repository = new CustomerRepositoryFake();
        $validator = new CustomerValidatorFake();
        $validator->updateResult = new CustomerValidationResult(['first_name' => 'Jane'], null);

        $service = new CustomerService($repository, $validator);
        $result = $service->updateCustomer(15, ['first_name' => 'Jane'], null);

        $this->assertIsArray($result);
        $this->assertSame('Jane', $repository->lastUpdate['data']['first_name']);
        $this->assertSame(15, $repository->lastUpdate['id']);
    }

    public function test_delete_customer_soft(): void
    {
        $repository = new CustomerRepositoryFake();
        $service = new CustomerService($repository, new CustomerValidatorFake());

        $result = $service->deleteCustomer(5, false, 3);

        $this->assertIsArray($result);
        $this->assertFalse($result['hard']);
        $this->assertSame([5, 3], $repository->lastSoftDelete);
    }

    public function test_bulk_block_requires_ids(): void
    {
        $service = new CustomerService(new CustomerRepositoryFake(), new CustomerValidatorFake());
        $result = $service->bulkAction(['action' => 'block', 'ids' => []], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('bad_request', $result->get_error_code());
    }

    public function test_bulk_block_updates_status(): void
    {
        $repository = new CustomerRepositoryFake();
        $service = new CustomerService($repository, new CustomerValidatorFake());

        $result = $service->bulkAction(['action' => 'block', 'ids' => [1, 2, 3]], 1);

        $this->assertIsArray($result);
        $this->assertSame([1, 2, 3], $repository->lastBulkStatus['ids']);
        $this->assertSame('blocked', $repository->lastBulkStatus['status']);
        $this->assertSame(1, $repository->lastBulkStatus['tenant']);
    }

    public function test_bulk_save_create_handles_validation_error(): void
    {
        $validator = new CustomerValidatorFake();
        $validator->bulkResult = new CustomerValidationResult([], new CustomerValidationError('validation_error', 'fail', 422));
        $service = new CustomerService(new CustomerRepositoryFake(), $validator);

        $result = $service->bulkAction(['action' => 'save', 'payload' => ['email' => '']], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_error', $result->get_error_code());
    }

    public function test_bulk_save_update_persists_changes(): void
    {
        $repository = new CustomerRepositoryFake();
        $validator = new CustomerValidatorFake();
        $validator->bulkResult = new CustomerValidationResult(['first_name' => 'Max'], null);

        $service = new CustomerService($repository, $validator);
        $result = $service->bulkAction(['action' => 'save', 'payload' => ['id' => 77, 'first_name' => 'Max']], 4);

        $this->assertIsArray($result);
        $this->assertSame(77, $repository->lastUpdate['id']);
        $this->assertSame('Max', $repository->lastUpdate['data']['first_name']);
    }
}

class CustomerRepositoryFake extends CustomerRepository
{
    public ?array $findReturn = null;

    public ?array $listReturn = null;

    public ?string $statusReturn = 'active';

    public ?int $nextInsertId = null;

    /** @var array<string, mixed>|null */
    public ?array $lastInsert = null;

    /** @var array{data: array<string, mixed>, id: int, tenant: ?int}|null */
    public ?array $lastUpdate = null;

    /** @var array{ids: list<int>, status: string, tenant: ?int}|null */
    public ?array $lastBulkStatus = null;

    /** @var array<int, mixed>|null */
    public ?array $lastSoftDelete = null;

    /** @var array<int, mixed>|null */
    public ?array $lastHardDelete = null;

    /** @var array{0:int,1:?int}|null */
    public ?array $lastFindCall = null;

    /** @var array<string, mixed>|null */
    public ?array $lastListFilters = null;

    public ?int $lastListTenantId = null;

    public function __construct()
    {
        // Intentionally empty to avoid parent initialisation.
    }

    public function find(int $id, ?int $tenantId): ?array
    {
        $this->checkException();
        $this->lastFindCall = [$id, $tenantId];
        return $this->findReturn;
    }

    public function list(array $filters, ?int $tenantId): array
    {
        $this->checkException();
        $this->lastListFilters = $filters;
        $this->lastListTenantId = $tenantId;
        return $this->listReturn ?? ['data' => [], 'total' => 0, 'limit' => $filters['limit'], 'offset' => $filters['offset']];
    }

    public function getStatus(int $id): ?string
    {
        $this->checkException();
        return $this->statusReturn;
    }

    public function insert(array $data): int
    {
        $this->checkException();
        $this->lastInsert = $data;
        return $this->nextInsertId ?? 1;
    }

    public function update(int $id, array $data, ?int $tenantId): int
    {
        $this->checkException();
        $this->lastUpdate = ['data' => $data, 'id' => $id, 'tenant' => $tenantId];
        return 1;
    }

    public function softDelete(int $id, ?int $tenantId): void
    {
        $this->checkException();
        $this->lastSoftDelete = [$id, $tenantId];
    }

    public function hardDelete(int $id, ?int $tenantId): void
    {
        $this->checkException();
        $this->lastHardDelete = [$id, $tenantId];
    }

    public function bulkUpdateStatus(array $ids, string $status, ?int $tenantId): int
    {
        $this->checkException();
        $this->lastBulkStatus = ['ids' => $ids, 'status' => $status, 'tenant' => $tenantId];
        return count($ids);
    }

    public function bulkSoftDelete(array $ids, ?int $tenantId): void
    {
        $this->checkException();
        $this->lastSoftDelete = [$ids, $tenantId];
    }

    public function bulkHardDelete(array $ids, ?int $tenantId): void
    {
        $this->checkException();
        $this->lastHardDelete = [$ids, $tenantId];
    }

    private ?RuntimeException $exception = null;

    public function setException(RuntimeException $exception): void
    {
        $this->exception = $exception;
    }

    private function checkException(): void
    {
        if ($this->exception) {
            throw $this->exception;
        }
    }
}

class CustomerValidatorFake extends CustomerValidator
{
    public ?CustomerValidationResult $createResult = null;

    public ?CustomerValidationResult $updateResult = null;

    public ?CustomerValidationResult $bulkResult = null;

    public array $lastCreatePayload = [];

    public array $lastUpdatePayload = [];

    public array $lastBulkPayload = [];

    public function __construct()
    {
        // skip parent initialisation
    }

    public function validateCreate(array $input): CustomerValidationResult
    {
        $this->lastCreatePayload = $input;
        return $this->createResult ?? new CustomerValidationResult($input, null);
    }

    public function validateUpdate(array $input, string $currentStatus): CustomerValidationResult
    {
        $this->lastUpdatePayload = $input;
        return $this->updateResult ?? new CustomerValidationResult($input, null);
    }

    public function validateBulkSave(array $input, bool $isCreate, string $currentStatus): CustomerValidationResult
    {
        $this->lastBulkPayload = $input;
        return $this->bulkResult ?? new CustomerValidationResult($input, null);
    }
}
