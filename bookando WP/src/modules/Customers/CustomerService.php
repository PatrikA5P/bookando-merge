<?php

declare(strict_types=1);

namespace Bookando\Modules\Customers;

use RuntimeException;
use WP_Error;
use Bookando\Core\Util\Sanitizer;
use function __;
use function current_time;
use function sanitize_key;
use function sanitize_text_field;
use function wp_json_encode;

class CustomerService
{
    private CustomerRepository $repository;

    private CustomerValidator $validator;

    public function __construct(?CustomerRepository $repository = null, ?CustomerValidator $validator = null)
    {
        $this->repository = $repository ?? new CustomerRepository();
        $this->validator  = $validator ?? new CustomerValidator();
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function getCustomer(int $id, int $tenantId)
    {
        try {
            $customer = $this->repository->find($id, $tenantId);
        } catch (RuntimeException $e) {
            return $this->toDatabaseError($e);
        }

        if (!$customer) {
            return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
        }

        if ($this->isHardDeleted($customer)) {
            return new WP_Error('gone', __('Nicht mehr verfügbar.', 'bookando'), ['status' => 410]);
        }

        return $customer;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|WP_Error
     */
    public function listCustomers(array $query, int $tenantId)
    {
        $filters = $this->normalizeListQuery($query);

        try {
            return $this->repository->list($filters, $tenantId);
        } catch (RuntimeException $e) {
            return $this->toDatabaseError($e);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|WP_Error
     */
    public function createCustomer(array $payload, int $tenantId)
    {
        $result = $this->validator->validateCreate($payload);
        if (!$result->isValid()) {
            return $this->toValidationError($result);
        }

        $data = $result->data();
        // Strikte Tenant-Isolation: Verwende IMMER die übergebene tenant_id
        $data['tenant_id']  = $tenantId;
        $data['roles']      = wp_json_encode(['customer']);
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        $data['deleted_at'] = null;

        try {
            $id = $this->repository->insert($data);
        } catch (RuntimeException $e) {
            return $this->toDatabaseError($e);
        }

        return ['id' => $id];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|WP_Error
     */
    public function updateCustomer(int $id, array $payload, int $tenantId)
    {
        try {
            $currentStatus = $this->repository->getStatus($id) ?? 'active';
        } catch (RuntimeException $e) {
            return $this->toDatabaseError($e);
        }

        $result = $this->validator->validateUpdate($payload, $currentStatus);
        if (!$result->isValid()) {
            return $this->toValidationError($result);
        }

        $data = $result->data();
        $updates = [];

        foreach ([
            'first_name', 'last_name', 'email', 'phone', 'address', 'address_2', 'zip', 'city', 'country',
            'birthdate', 'gender', 'language', 'note', 'description', 'avatar_url', 'timezone', 'external_id', 'status'
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        $updates['updated_at'] = current_time('mysql');

        try {
            $this->repository->update($id, $updates, $tenantId);
        } catch (RuntimeException $e) {
            return $this->toDatabaseError($e);
        }

        return ['updated' => true];
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function deleteCustomer(int $id, bool $hard, int $tenantId)
    {
        try {
            if ($hard) {
                $this->repository->hardDelete($id, $tenantId);
            } else {
                $this->repository->softDelete($id, $tenantId);
            }
        } catch (RuntimeException $e) {
            return $this->toDatabaseError($e);
        }

        return ['deleted' => true, 'hard' => $hard];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|WP_Error
     */
    public function bulkAction(array $payload, int $tenantId)
    {
        $action = Sanitizer::key($payload['action'] ?? '');
        $ids = $this->normalizeIds($payload['ids'] ?? []);
        $data = $payload['payload'] ?? [];

        switch ($action) {
            case 'block':
            case 'activate':
                if (empty($ids)) {
                    return new WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
                }
                $status = $action === 'block' ? 'blocked' : 'active';
                try {
                    $affected = $this->repository->bulkUpdateStatus($ids, $status, $tenantId);
                } catch (RuntimeException $e) {
                    return $this->toDatabaseError($e);
                }
                return ['ok' => true, 'affected' => $affected];

            case 'soft_delete':
                if (empty($ids)) {
                    return new WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
                }
                try {
                    $this->repository->bulkSoftDelete($ids, $tenantId);
                } catch (RuntimeException $e) {
                    return $this->toDatabaseError($e);
                }
                return ['ok' => true, 'affected' => count($ids)];

            case 'hard_delete':
                if (empty($ids)) {
                    return new WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
                }
                try {
                    $this->repository->bulkHardDelete($ids, $tenantId);
                } catch (RuntimeException $e) {
                    return $this->toDatabaseError($e);
                }
                return ['ok' => true, 'affected' => count($ids)];

            case 'save':
                $isCreate = empty($data['id']);
                $currentStatus = 'active';
                if (!$isCreate) {
                    try {
                        $currentStatus = $this->repository->getStatus((int) $data['id']) ?? 'active';
                    } catch (RuntimeException $e) {
                        return $this->toDatabaseError($e);
                    }
                }

                $result = $this->validator->validateBulkSave((array) $data, $isCreate, $currentStatus);
                if (!$result->isValid()) {
                    return $this->toValidationError($result);
                }

                $normalized = $result->data();

                if ($isCreate) {
                    // Strikte Tenant-Isolation: Verwende IMMER die übergebene tenant_id
                    $normalized['tenant_id']  = $tenantId;
                    $normalized['roles']      = wp_json_encode(['customer']);
                    $normalized['created_at'] = current_time('mysql');
                    $normalized['updated_at'] = current_time('mysql');
                    $normalized['deleted_at'] = null;

                    try {
                        $id = $this->repository->insert($normalized);
                    } catch (RuntimeException $e) {
                        return $this->toDatabaseError($e);
                    }

                    return ['ok' => true, 'id' => $id];
                }

                $id = (int) $data['id'];
                $updates = [];
                foreach ([
                    'first_name', 'last_name', 'email', 'phone', 'address', 'address_2', 'zip', 'city', 'country',
                    'birthdate', 'gender', 'language', 'note', 'description', 'avatar_url', 'timezone', 'external_id', 'status'
                ] as $field) {
                    if (array_key_exists($field, $normalized)) {
                        $updates[$field] = $normalized[$field];
                    }
                }
                $updates['updated_at'] = current_time('mysql');

                try {
                    $this->repository->update($id, $updates, $tenantId);
                } catch (RuntimeException $e) {
                    return $this->toDatabaseError($e);
                }

                return ['ok' => true, 'updated' => true, 'id' => $id];

            default:
                return new WP_Error('bad_request', __('Unbekannte Aktion.', 'bookando'), ['status' => 400]);
        }
    }

    /**
     * @param array<string, mixed> $customer
     */
    private function isHardDeleted(array $customer): bool
    {
        return isset($customer['status'], $customer['deleted_at'])
            && $customer['status'] === 'deleted'
            && !empty($customer['deleted_at']);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function normalizeListQuery(array $query): array
    {
        $includeDeleted = Sanitizer::key($query['include_deleted'] ?? 'no');
        if (!in_array($includeDeleted, ['no', 'soft', 'all'], true)) {
            $includeDeleted = 'no';
        }

        $limit = (int) ($query['limit'] ?? 50);
        $limit = max(1, min(200, $limit));

        $offset = max(0, (int) ($query['offset'] ?? 0));

        $order = Sanitizer::key($query['order'] ?? 'last_name');
        $allowedOrder = ['first_name', 'last_name', 'email', 'created_at', 'updated_at', 'id'];
        if (!in_array($order, $allowedOrder, true)) {
            $order = 'last_name';
        }

        $dir = strtoupper((string) ($query['dir'] ?? 'ASC'));
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        $search = Sanitizer::text(is_scalar($query['search'] ?? null) ? (string) $query['search'] : '');

        return [
            'include_deleted' => $includeDeleted,
            'limit'           => $limit,
            'offset'          => $offset,
            'order'           => $order,
            'dir'             => $dir,
            'search'          => $search,
        ];
    }

    /**
     * @param mixed $ids
     * @return list<int>
     */
    private function normalizeIds($ids): array
    {
        $result = [];
        foreach ((array) $ids as $id) {
            $value = (int) $id;
            if ($value > 0) {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    private function toValidationError(CustomerValidationResult $result): WP_Error
    {
        $error = $result->error();
        if (!$error) {
            return new WP_Error('validation_error', __('Validierung fehlgeschlagen.', 'bookando'), ['status' => 400]);
        }

        $data = ['status' => $error->status()];
        $details = $error->details();
        if (!empty($details)) {
            $data = array_merge($data, $details);
        }

        return new WP_Error($error->code(), $error->message(), $data);
    }

    private function toDatabaseError(RuntimeException $exception): WP_Error
    {
        return new WP_Error('db_error', $exception->getMessage(), ['status' => 500]);
    }
}
