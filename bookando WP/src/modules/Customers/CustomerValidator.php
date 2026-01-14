<?php
namespace Bookando\Modules\Customers;

use Bookando\Core\Settings\FormRules;
use Bookando\Core\Util\Sanitizer;
use function __;
use function is_email;

class CustomerValidator
{
    /** @var array<string, mixed> */
    private array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? FormRules::get('customers', 'admin');
    }

    public function validateCreate(array $input): CustomerValidationResult
    {
        return $this->validate($input, true, null);
    }

    public function validateUpdate(array $input, string $currentStatus): CustomerValidationResult
    {
        return $this->validate($input, false, $currentStatus);
    }

    public function validateBulkSave(array $input, bool $isCreate, string $currentStatus): CustomerValidationResult
    {
        return $this->validate($input, $isCreate, $currentStatus);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validate(array $input, bool $isCreate, ?string $currentStatus): CustomerValidationResult
    {
        $normalized = $this->normalize($input, $isCreate);
        $targetStatus = $normalized['status'] ?? ($currentStatus ?? 'active');

        if ($isCreate) {
            $normalized['status'] = $targetStatus;
        }

        if (!empty($normalized['email']) && !is_email($normalized['email'])) {
            return new CustomerValidationResult(
                $normalized,
                new CustomerValidationError('invalid_email', __('Ungültige E-Mail-Adresse.', 'bookando'), 400)
            );
        }

        if ($targetStatus !== 'deleted') {
            $missing = $this->validateFormRules($normalized + ['status' => $targetStatus], $targetStatus);
            if (!empty($missing)) {
                return new CustomerValidationResult(
                    $normalized,
                    new CustomerValidationError(
                        'validation_error',
                        __('Pflichtfelder fehlen.', 'bookando'),
                        422,
                        ['fields' => $missing]
                    )
                );
            }
        }

        return new CustomerValidationResult($normalized, null);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalize(array $input, bool $isCreate): array
    {
        $output = [];

        $copyNullIfEmpty = function (string $key) use (&$output, $input, $isCreate): void {
            if ($isCreate || array_key_exists($key, $input)) {
                $output[$key] = Sanitizer::nullIfEmpty($input[$key] ?? null);
            }
        };

        foreach (['first_name', 'last_name', 'address', 'address_2', 'zip', 'city', 'note', 'description', 'avatar_url', 'timezone', 'external_id'] as $field) {
            $copyNullIfEmpty($field);
        }

        if ($isCreate || array_key_exists('email', $input)) {
            $emailRaw = $input['email'] ?? '';
            $email = Sanitizer::email(is_scalar($emailRaw) ? (string) $emailRaw : '');
            $output['email'] = $email !== '' ? $email : null;
        }

        if ($isCreate || array_key_exists('phone', $input)) {
            $output['phone'] = Sanitizer::phone($input['phone'] ?? null);
        }

        if ($isCreate || array_key_exists('country', $input)) {
            $output['country'] = $this->normalizeCountry($input['country'] ?? null);
        }

        if ($isCreate || array_key_exists('language', $input)) {
            $output['language'] = Sanitizer::language($input['language'] ?? null) ?? 'de';
        }

        if ($isCreate || array_key_exists('birthdate', $input)) {
            $output['birthdate'] = $this->normalizeBirthdate($input['birthdate'] ?? null);
        }

        if ($isCreate || array_key_exists('gender', $input)) {
            $output['gender'] = $this->normalizeGender($input['gender'] ?? null);
        }

        if ($isCreate || array_key_exists('status', $input)) {
            $output['status'] = $this->normalizeStatus((string) ($input['status'] ?? 'active'));
        }

        if ($isCreate || array_key_exists('tenant_id', $input)) {
            $output['tenant_id'] = isset($input['tenant_id']) ? (int) $input['tenant_id'] : null;
        }

        return $output;
    }

    private function normalizeStatus(string $status): string
    {
        $value = strtolower(trim($status));
        if (in_array($value, ['active', 'blocked', 'deleted'], true)) {
            return $value;
        }

        if (in_array($value, ['inactive', 'deactivated'], true)) {
            return 'blocked';
        }

        return 'active';
    }

    private function normalizeGender(mixed $gender): ?string
    {
        $value = strtolower(trim((string) $gender));
        if ($value === '') {
            return null;
        }

        $map = [
            'male' => 'm',
            'female' => 'f',
            'other' => 'd',
            'none' => 'n',
            'm' => 'm',
            'f' => 'f',
            'd' => 'd',
            'n' => 'n',
            'männlich' => 'm',
            'weiblich' => 'f',
            'divers' => 'd',
            'keine angabe' => 'n',
        ];

        return $map[$value] ?? null;
    }

    private function normalizeCountry(mixed $country): ?string
    {
        if (is_array($country)) {
            $country = $country['code'] ?? $country['value'] ?? null;
        }

        $value = strtoupper(trim((string) $country));
        if ($value === '') {
            return null;
        }

        return preg_match('/^[A-Z]{2}$/', $value) ? $value : null;
    }

    private function normalizeBirthdate(mixed $birthdate): ?string
    {
        $value = trim((string) ($birthdate ?? ''));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $matches)) {
            return sprintf('%s-%s-%s', $matches[3], $matches[2], $matches[1]);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function validateFormRules(array $data, string $targetStatus): array
    {
        $missing = [];

        foreach (($this->rules['fields'] ?? []) as $field => $config) {
            if ($this->fieldRequiredByWhen((array) $config, $targetStatus)) {
                $value = $data[$field] ?? null;
                if ($value === null || $value === '') {
                    $missing[] = $field;
                }
            }
        }

        foreach (($this->rules['groups']['at_least_one_of'] ?? []) as $group) {
            $hasValue = false;
            foreach ((array) $group as $field) {
                $value = $data[$field] ?? null;
                if ($value !== null && $value !== '') {
                    $hasValue = true;
                    break;
                }
            }

            if (!$hasValue) {
                $missing[] = 'at_least_one_of:' . implode('|', (array) $group);
            }
        }

        return $missing;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function fieldRequiredByWhen(array $config, string $status): bool
    {
        if (empty($config['required'])) {
            return false;
        }

        $when = (array) ($config['when'] ?? []);
        $allowed = true;

        if (!empty($when['status_is'])) {
            $allowed = in_array($status, (array) $when['status_is'], true);
        }

        if (!empty($when['status_not'])) {
            if (in_array($status, (array) $when['status_not'], true)) {
                $allowed = false;
            }
        }

        return $allowed;
    }
}
