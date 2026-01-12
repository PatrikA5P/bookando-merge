<?php

namespace Bookando\Tests\Unit\Modules\Customers;

use Bookando\Modules\customers\CustomerRepository;
use Bookando\Modules\customers\CustomerService;
use Bookando\Modules\customers\CustomerValidationError;
use Bookando\Modules\customers\CustomerValidationResult;
use Bookando\Modules\customers\CustomerValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WP_Error;

final class CustomerServiceTest extends TestCase
{
    /** @var CustomerRepository&MockObject */
    private CustomerRepository $repository;

    /** @var CustomerValidator&MockObject */
    private CustomerValidator $validator;

    private CustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();

        $this->repository = $this->createMock(CustomerRepository::class);
        $this->validator  = $this->createMock(CustomerValidator::class);

        $this->service = new CustomerService($this->repository, $this->validator);
    }

    public function test_get_customer_returns_customer_when_found(): void
    {
        $customer = ['id' => 7, 'status' => 'active'];

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(7, 3)
            ->willReturn($customer);

        $result = $this->service->getCustomer(7, 3);

        $this->assertSame($customer, $result);
    }

    public function test_get_customer_returns_error_when_not_found(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(11, null)
            ->willReturn(null);

        $result = $this->service->getCustomer(11, null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('not_found', $result->get_error_code());
    }

    public function test_get_customer_returns_error_for_hard_deleted_record(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(2, 5)
            ->willReturn([
                'id'         => 2,
                'status'     => 'deleted',
                'deleted_at' => '2024-01-01 10:00:00',
            ]);

        $result = $this->service->getCustomer(2, 5);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('gone', $result->get_error_code());
    }

    public function test_get_customer_returns_database_error_on_exception(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willThrowException(new RuntimeException('boom'));

        $result = $this->service->getCustomer(1, null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_list_customers_normalizes_query_and_delegates_to_repository(): void
    {
        $expected = ['data' => [], 'total' => 0, 'limit' => 50, 'offset' => 0];

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with($this->callback(function (array $filters): bool {
                $this->assertSame('no', $filters['include_deleted']);
                $this->assertSame(200, $filters['limit']);
                $this->assertSame(0, $filters['offset']);
                $this->assertSame('last_name', $filters['order']);
                $this->assertSame('ASC', $filters['dir']);
                $this->assertSame('search term', $filters['search']);
                return true;
            }), 9)
            ->willReturn($expected);

        $query = [
            'include_deleted' => 'invalid',
            'limit'           => 500,
            'offset'          => -5,
            'order'           => 'drop table',
            'dir'             => 'ignored',
            'search'          => '  search term  ',
        ];

        $result = $this->service->listCustomers($query, 9);

        $this->assertSame($expected, $result);
    }

    public function test_list_customers_returns_error_when_repository_fails(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('list')
            ->willThrowException(new RuntimeException('sql failed'));

        $result = $this->service->listCustomers([], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_create_customer_returns_validation_error(): void
    {
        $error = new CustomerValidationError('invalid_email', 'invalid', 422);
        $this->validator
            ->expects($this->once())
            ->method('validateCreate')
            ->with(['email' => 'bad'])
            ->willReturn(new CustomerValidationResult([], $error));

        $result = $this->service->createCustomer(['email' => 'bad'], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_email', $result->get_error_code());
    }

    public function test_create_customer_inserts_normalized_payload(): void
    {
        $normalized = [
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
        ];

        $this->validator
            ->expects($this->once())
            ->method('validateCreate')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('Jane', $payload['first_name']);
                return true;
            }))
            ->willReturn(new CustomerValidationResult($normalized, null));

        $this->repository
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (array $data): bool {
                $this->assertSame('Jane', $data['first_name']);
                $this->assertSame('Doe', $data['last_name']);
                $this->assertSame('jane@example.com', $data['email']);
                $this->assertSame(4, $data['tenant_id']);
                $this->assertSame('["customer"]', $data['roles']);
                $this->assertSame('2025-01-01 12:00:00', $data['created_at']);
                $this->assertSame('2025-01-01 12:00:00', $data['updated_at']);
                $this->assertNull($data['deleted_at']);
                return true;
            }))
            ->willReturn(99);

        $result = $this->service->createCustomer(['first_name' => 'Jane'], 4);

        $this->assertSame(['id' => 99], $result);
    }

    public function test_create_customer_returns_error_when_insert_fails(): void
    {
        $this->validator
            ->method('validateCreate')
            ->willReturn(new CustomerValidationResult(['first_name' => 'John'], null));

        $this->repository
            ->expects($this->once())
            ->method('insert')
            ->willThrowException(new RuntimeException('failed'));

        $result = $this->service->createCustomer(['first_name' => 'John'], 2);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_update_customer_returns_validation_error(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->with(13)
            ->willReturn('active');

        $error = new CustomerValidationError('validation_error', 'fail', 422, ['fields' => ['email']]);

        $this->validator
            ->expects($this->once())
            ->method('validateUpdate')
            ->with(['email' => ''], 'active')
            ->willReturn(new CustomerValidationResult([], $error));

        $result = $this->service->updateCustomer(13, ['email' => ''], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_error', $result->get_error_code());
    }

    public function test_update_customer_updates_allowed_fields(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->with(5)
            ->willReturn('blocked');

        $data = [
            'first_name' => 'Max',
            'status'     => 'active',
            'unknown'    => 'ignored',
        ];

        $this->validator
            ->expects($this->once())
            ->method('validateUpdate')
            ->with(['first_name' => 'Max'], 'blocked')
            ->willReturn(new CustomerValidationResult($data, null));

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(
                5,
                $this->callback(function (array $updates): bool {
                    $this->assertSame('Max', $updates['first_name']);
                    $this->assertSame('active', $updates['status']);
                    $this->assertSame('2025-01-01 12:00:00', $updates['updated_at']);
                    $this->assertArrayNotHasKey('unknown', $updates);
                    return true;
                }),
                8
            );

        $result = $this->service->updateCustomer(5, ['first_name' => 'Max'], 8);

        $this->assertSame(['updated' => true], $result);
    }

    public function test_update_customer_returns_error_when_get_status_fails(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->willThrowException(new RuntimeException('db down'));

        $result = $this->service->updateCustomer(1, [], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_update_customer_returns_error_when_update_fails(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn('active');

        $this->validator
            ->expects($this->once())
            ->method('validateUpdate')
            ->willReturn(new CustomerValidationResult(['first_name' => 'Paula'], null));

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new RuntimeException('write failed'));

        $result = $this->service->updateCustomer(3, ['first_name' => 'Paula'], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_delete_customer_soft_delete_path(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('softDelete')
            ->with(4, 2);

        $result = $this->service->deleteCustomer(4, false, 2);

        $this->assertSame(['deleted' => true, 'hard' => false], $result);
    }

    public function test_delete_customer_hard_delete_path(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('hardDelete')
            ->with(4, null);

        $result = $this->service->deleteCustomer(4, true, null);

        $this->assertSame(['deleted' => true, 'hard' => true], $result);
    }

    public function test_delete_customer_returns_error_when_repository_fails(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('softDelete')
            ->willThrowException(new RuntimeException('fail'));

        $result = $this->service->deleteCustomer(5, false, null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_bulk_block_requires_ids(): void
    {
        $result = $this->service->bulkAction(['action' => 'block', 'ids' => []], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('bad_request', $result->get_error_code());
    }

    public function test_bulk_block_updates_status(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('bulkUpdateStatus')
            ->with([2, 5], 'blocked', 6)
            ->willReturn(2);

        $result = $this->service->bulkAction(['action' => 'block', 'ids' => ['2', 5, '2']], 6);

        $this->assertSame(['ok' => true, 'affected' => 2], $result);
    }

    public function test_bulk_activate_updates_status(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('bulkUpdateStatus')
            ->with([3], 'active', null)
            ->willReturn(1);

        $result = $this->service->bulkAction(['action' => 'activate', 'ids' => [0, 3]], null);

        $this->assertSame(['ok' => true, 'affected' => 1], $result);
    }

    public function test_bulk_soft_delete_invokes_repository(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('bulkSoftDelete')
            ->with([1, 2], 4);

        $result = $this->service->bulkAction(['action' => 'soft_delete', 'ids' => [1, 2]], 4);

        $this->assertSame(['ok' => true, 'affected' => 2], $result);
    }

    public function test_bulk_hard_delete_invokes_repository(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('bulkHardDelete')
            ->with([9], null);

        $result = $this->service->bulkAction(['action' => 'hard_delete', 'ids' => [9]], null);

        $this->assertSame(['ok' => true, 'affected' => 1], $result);
    }

    public function test_bulk_save_returns_validation_error(): void
    {
        $error = new CustomerValidationError('validation_error', 'fail', 422);
        $this->validator
            ->expects($this->once())
            ->method('validateBulkSave')
            ->with([], true, 'active')
            ->willReturn(new CustomerValidationResult([], $error));

        $result = $this->service->bulkAction(['action' => 'save', 'payload' => []], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_error', $result->get_error_code());
    }

    public function test_bulk_save_create_inserts_customer(): void
    {
        $normalized = ['first_name' => 'Anna'];

        $this->validator
            ->expects($this->once())
            ->method('validateBulkSave')
            ->with(['first_name' => 'Anna'], true, 'active')
            ->willReturn(new CustomerValidationResult($normalized, null));

        $this->repository
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (array $data): bool {
                $this->assertSame('Anna', $data['first_name']);
                $this->assertSame(3, $data['tenant_id']);
                $this->assertSame('["customer"]', $data['roles']);
                $this->assertNull($data['deleted_at']);
                return true;
            }))
            ->willReturn(55);

        $result = $this->service->bulkAction([
            'action'  => 'save',
            'payload' => ['first_name' => 'Anna'],
        ], 3);

        $this->assertSame(['ok' => true, 'id' => 55], $result);
    }

    public function test_bulk_save_update_updates_customer(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->with(10)
            ->willReturn('blocked');

        $normalized = ['id' => 10, 'first_name' => 'Paul'];

        $this->validator
            ->expects($this->once())
            ->method('validateBulkSave')
            ->with(['id' => 10, 'first_name' => 'Paul'], false, 'blocked')
            ->willReturn(new CustomerValidationResult($normalized, null));

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(
                10,
                $this->callback(function (array $updates): bool {
                    $this->assertSame('Paul', $updates['first_name']);
                    $this->assertSame('2025-01-01 12:00:00', $updates['updated_at']);
                    return true;
                }),
                2
            );

        $result = $this->service->bulkAction([
            'action'  => 'save',
            'payload' => ['id' => 10, 'first_name' => 'Paul'],
        ], 2);

        $this->assertSame(['ok' => true, 'updated' => true, 'id' => 10], $result);
    }

    public function test_bulk_save_returns_error_when_get_status_fails(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->willThrowException(new RuntimeException('db error'));

        $result = $this->service->bulkAction([
            'action'  => 'save',
            'payload' => ['id' => 5],
        ], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_bulk_save_returns_error_when_insert_fails(): void
    {
        $this->validator
            ->method('validateBulkSave')
            ->willReturn(new CustomerValidationResult([], null));

        $this->repository
            ->expects($this->once())
            ->method('insert')
            ->willThrowException(new RuntimeException('insert fail'));

        $result = $this->service->bulkAction(['action' => 'save', 'payload' => []], 1);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_bulk_save_returns_error_when_update_fails(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn('active');

        $this->validator
            ->expects($this->once())
            ->method('validateBulkSave')
            ->willReturn(new CustomerValidationResult(['id' => 4], null));

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new RuntimeException('update fail'));

        $result = $this->service->bulkAction([
            'action'  => 'save',
            'payload' => ['id' => 4],
        ], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('db_error', $result->get_error_code());
    }

    public function test_bulk_action_returns_error_for_unknown_action(): void
    {
        $result = $this->service->bulkAction(['action' => 'unknown'], null);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('bad_request', $result->get_error_code());
    }
}
