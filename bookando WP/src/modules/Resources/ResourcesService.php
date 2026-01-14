<?php

declare(strict_types=1);

namespace Bookando\Modules\Resources;

use Bookando\Core\Auth\Gate;
use Bookando\Core\Util\Sanitizer;
use Bookando\Modules\resources\Capabilities;
use DateTimeImmutable;
use WP_Error;
use function __;
use function array_key_exists;
use function array_values;
use function in_array;
use function is_array;
use function is_scalar;
use function is_numeric;
use function is_string;
use function mb_strlen;
use function preg_match;
use function sprintf;
use function trim;

final class ResourcesService
{
    private const MAX_NAME_LENGTH = 255;

    private ResourcesRepository $repository;

    /** @var callable */
    private $canManage;

    /**
     * @param callable|null $canManageResolver Returns true when the current actor is allowed to manage resources.
     */
    public function __construct(?ResourcesRepository $repository = null, ?callable $canManageResolver = null)
    {
        $this->repository = $repository ?? new ResourcesRepository();
        $this->canManage  = $canManageResolver ?? static fn(): bool => Gate::canManage('resources');
    }

    public function getState(): array
    {
        return $this->repository->getState();
    }

    /**
     * @return array<int, array<string, mixed>>|WP_Error
     */
    public function listByType(string $type)
    {
        $normalizedType = $this->normalizeType($type);
        if ($normalizedType === null) {
            return $this->invalidTypeError($type);
        }

        return $this->repository->listByType($normalizedType);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|WP_Error
     */
    public function save(string $type, array $payload)
    {
        if (!$this->canManage()) {
            return $this->forbiddenError();
        }

        $normalizedType = $this->normalizeType($type);
        if ($normalizedType === null) {
            return $this->invalidTypeError($type);
        }

        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_Error) {
            return $validation;
        }

        return $this->repository->save($normalizedType, $payload);
    }

    /**
     * @return array{deleted: bool, id: string}|WP_Error
     */
    public function delete(string $type, string $id)
    {
        if (!$this->canManage()) {
            return $this->forbiddenError();
        }

        $normalizedType = $this->normalizeType($type);
        if ($normalizedType === null) {
            return $this->invalidTypeError($type);
        }

        $id = Sanitizer::text($id);
        if ($id === '') {
            return new WP_Error(
                'validation_failed',
                __('Ressourcen-ID erforderlich.', 'bookando'),
                ['status' => 422, 'field' => 'id']
            );
        }

        $deleted = $this->repository->delete($normalizedType, $id);
        if (!$deleted) {
            return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
        }

        return ['deleted' => true, 'id' => $id];
    }

    private function canManage(): bool
    {
        return (bool) call_user_func($this->canManage);
    }

    private function invalidTypeError(string $type): WP_Error
    {
        return new WP_Error(
            'invalid_type',
            sprintf(__('Unbekannter Ressourcentyp %s.', 'bookando'), $type),
            ['status' => 400]
        );
    }

    private function forbiddenError(): WP_Error
    {
        return new WP_Error(
            'rest_forbidden',
            sprintf(
                __('Zusätzliche Berechtigung %s erforderlich.', 'bookando'),
                Capabilities::CAPABILITY_MANAGE
            ),
            ['status' => 403]
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return true|WP_Error
     */
    private function validatePayload(array $payload)
    {
        $name = Sanitizer::text(is_scalar($payload['name'] ?? null) ? (string) $payload['name'] : '');
        if ($name === '') {
            return new WP_Error(
                'validation_failed',
                __('Name erforderlich.', 'bookando'),
                ['status' => 422, 'field' => 'name']
            );
        }

        if (isset($payload['availability']) && !is_array($payload['availability'])) {
            return new WP_Error(
                'validation_failed',
                __('Ungültige Zeitfenster.', 'bookando'),
                ['status' => 422, 'field' => 'availability']
            );
        }

        return true;
    }

    private function normalizeType(string $type): ?string
    {
        $type = strtolower(trim($type));

        return in_array($type, ResourcesRepository::TYPES, true) ? $type : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|WP_Error
     */
    public static function validateResource(string $type, array $payload)
    {
        if (!in_array($type, ResourcesRepository::TYPES, true)) {
            return new WP_Error(
                'invalid_resource_type',
                __('Unbekannter Ressourcentyp.', 'bookando'),
                ['status' => 400]
            );
        }

        $errors = [];
        $validated = [
            'type' => $type,
        ];

        if (isset($payload['id'])) {
            if (!is_string($payload['id'])) {
                self::addError($errors, 'id', 'type', __('Die ID muss eine Zeichenkette sein.', 'bookando'));
            } else {
                $validated['id'] = trim($payload['id']);
            }
        }

        if (isset($payload['type']) && $payload['type'] !== $type) {
            self::addError($errors, 'type', 'mismatch', __('Ressourcentyp stimmt nicht mit der Route überein.', 'bookando'));
        }

        $name = $payload['name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            self::addError($errors, 'name', 'required', __('Name ist ein Pflichtfeld.', 'bookando'));
        } else {
            $name = trim($name);
            if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
                self::addError($errors, 'name', 'max_length', sprintf(
                    __('Name darf maximal %d Zeichen lang sein.', 'bookando'),
                    self::MAX_NAME_LENGTH
                ));
            } else {
                $validated['name'] = $name;
            }
        }

        if (array_key_exists('description', $payload)) {
            if ($payload['description'] === null) {
                $validated['description'] = '';
            } elseif (!is_string($payload['description'])) {
                self::addError($errors, 'description', 'type', __('Beschreibung muss eine Zeichenkette sein.', 'bookando'));
            } else {
                $validated['description'] = trim($payload['description']);
            }
        } else {
            $validated['description'] = '';
        }

        if (!array_key_exists('capacity', $payload) || $payload['capacity'] === null || $payload['capacity'] === '') {
            $validated['capacity'] = null;
        } elseif (!is_numeric($payload['capacity'])) {
            self::addError($errors, 'capacity', 'type', __('Kapazität muss eine Zahl sein.', 'bookando'));
        } else {
            $capacity = (int) $payload['capacity'];
            if ($capacity < 0) {
                self::addError($errors, 'capacity', 'min', __('Kapazität darf nicht negativ sein.', 'bookando'));
            } else {
                $validated['capacity'] = $capacity;
            }
        }

        if (!isset($payload['tags']) || $payload['tags'] === null) {
            $validated['tags'] = [];
        } elseif (!is_array($payload['tags'])) {
            self::addError($errors, 'tags', 'type', __('Tags müssen als Array von Textwerten übergeben werden.', 'bookando'));
            $validated['tags'] = [];
        } else {
            $normalizedTags = [];
            foreach ($payload['tags'] as $index => $tag) {
                $field = sprintf('tags.%d', $index);
                if (!is_string($tag)) {
                    self::addError($errors, $field, 'type', __('Tag muss eine Zeichenkette sein.', 'bookando'));
                    continue;
                }

                $tag = trim($tag);
                if ($tag === '') {
                    self::addError($errors, $field, 'empty', __('Tag darf nicht leer sein.', 'bookando'));
                    continue;
                }

                $normalizedTags[] = $tag;
            }
            $validated['tags'] = array_values($normalizedTags);
        }

        $availability = $payload['availability'] ?? [];
        $validated['availability'] = [];
        if ($availability === null) {
            $availability = [];
        }

        if (!is_array($availability)) {
            self::addError($errors, 'availability', 'type', __('Verfügbarkeit muss als Array übergeben werden.', 'bookando'));
        } else {
            foreach (array_values($availability) as $index => $slot) {
                $slotField = sprintf('availability.%d', $index);
                if (!is_array($slot)) {
                    self::addError($errors, $slotField, 'type', __('Verfügbarkeits-Eintrag muss ein Array sein.', 'bookando'));
                    continue;
                }

                $normalizedSlot = [];

                if (isset($slot['id'])) {
                    if (!is_string($slot['id'])) {
                        self::addError($errors, $slotField . '.id', 'type', __('Slot-ID muss eine Zeichenkette sein.', 'bookando'));
                    } else {
                        $normalizedSlot['id'] = trim($slot['id']);
                    }
                }

                $date = $slot['date'] ?? null;
                if ($date === null || $date === '') {
                    $normalizedSlot['date'] = null;
                } elseif (!is_string($date)) {
                    self::addError($errors, $slotField . '.date', 'type', __('Datum muss eine Zeichenkette im Format JJJJ-MM-TT sein.', 'bookando'));
                } elseif (!self::isValidDate($date)) {
                    self::addError($errors, $slotField . '.date', 'format', __('Datum muss eine Zeichenkette im Format JJJJ-MM-TT sein.', 'bookando'));
                } else {
                    $normalizedSlot['date'] = substr($date, 0, 10);
                }

                $start = $slot['start'] ?? null;
                if ($start === null || $start === '') {
                    $normalizedSlot['start'] = null;
                } elseif (!is_string($start)) {
                    self::addError($errors, $slotField . '.start', 'type', __('Startzeit muss eine Zeichenkette im Format HH:MM sein.', 'bookando'));
                } else {
                    $normalizedStart = self::normalizeTime($start);
                    if ($normalizedStart === null) {
                        self::addError($errors, $slotField . '.start', 'format', __('Startzeit muss eine Zeichenkette im Format HH:MM sein.', 'bookando'));
                    } else {
                        $normalizedSlot['start'] = $normalizedStart;
                    }
                }

                $end = $slot['end'] ?? null;
                if ($end === null || $end === '') {
                    $normalizedSlot['end'] = null;
                } elseif (!is_string($end)) {
                    self::addError($errors, $slotField . '.end', 'type', __('Endzeit muss eine Zeichenkette im Format HH:MM sein.', 'bookando'));
                } else {
                    $normalizedEnd = self::normalizeTime($end);
                    if ($normalizedEnd === null) {
                        self::addError($errors, $slotField . '.end', 'format', __('Endzeit muss eine Zeichenkette im Format HH:MM sein.', 'bookando'));
                    } else {
                        $normalizedSlot['end'] = $normalizedEnd;
                    }
                }

                if (($normalizedSlot['start'] ?? null) !== null && ($normalizedSlot['end'] ?? null) !== null) {
                    if (self::compareTimes($normalizedSlot['start'], $normalizedSlot['end']) >= 0) {
                        self::addError($errors, $slotField . '.end', 'range', __('Endzeit muss nach der Startzeit liegen.', 'bookando'));
                    }
                }

                $slotCapacity = $slot['capacity'] ?? null;
                if ($slotCapacity === null || $slotCapacity === '') {
                    $normalizedSlot['capacity'] = null;
                } elseif (!is_numeric($slotCapacity)) {
                    self::addError($errors, $slotField . '.capacity', 'type', __('Kapazität für den Slot muss eine Zahl sein.', 'bookando'));
                } else {
                    $capacityValue = (int) $slotCapacity;
                    if ($capacityValue < 0) {
                        self::addError($errors, $slotField . '.capacity', 'min', __('Kapazität für den Slot darf nicht negativ sein.', 'bookando'));
                    } else {
                        $normalizedSlot['capacity'] = $capacityValue;
                    }
                }

                $notes = $slot['notes'] ?? '';
                if ($notes === null) {
                    $notes = '';
                }

                if (!is_string($notes)) {
                    self::addError($errors, $slotField . '.notes', 'type', __('Notizen müssen eine Zeichenkette sein.', 'bookando'));
                } else {
                    $normalizedSlot['notes'] = $notes;
                }

                $validated['availability'][] = $normalizedSlot + [
                    'date'     => $normalizedSlot['date'] ?? null,
                    'start'    => $normalizedSlot['start'] ?? null,
                    'end'      => $normalizedSlot['end'] ?? null,
                    'capacity' => $normalizedSlot['capacity'] ?? null,
                    'notes'    => $normalizedSlot['notes'] ?? '',
                ];
            }
        }

        foreach (['created_at', 'updated_at'] as $timestampField) {
            if (!array_key_exists($timestampField, $payload) || $payload[$timestampField] === null) {
                continue;
            }

            if (!is_string($payload[$timestampField])) {
                self::addError($errors, $timestampField, 'type', __('Zeitstempel müssen Zeichenketten sein.', 'bookando'));
                continue;
            }

            $validated[$timestampField] = $payload[$timestampField];
        }

        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                __('Validierung der Ressourcendaten fehlgeschlagen.', 'bookando'),
                [
                    'status' => 422,
                    'fields' => $errors,
                ]
            );
        }

        if (!array_key_exists('name', $validated)) {
            $validated['name'] = '';
        }

        return $validated;
    }

    /**
     * @param array<string, array<int, array{code: string, message: string}>> $errors
     */
    private static function addError(array &$errors, string $field, string $code, string $message): void
    {
        $errors[$field][] = [
            'code'    => $code,
            'message' => $message,
        ];
    }

    private static function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return null;
        }

        return substr($value, 0, 5);
    }

    private static function compareTimes(string $start, string $end): int
    {
        $startMinutes = ((int) substr($start, 0, 2)) * 60 + (int) substr($start, 3, 2);
        $endMinutes   = ((int) substr($end, 0, 2)) * 60 + (int) substr($end, 3, 2);

        return $startMinutes <=> $endMinutes;
    }

    private static function isValidDate(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dt === false) {
            return false;
        }

        return $dt->format('Y-m-d') === $value;
    }
}
