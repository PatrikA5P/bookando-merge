<?php

declare(strict_types=1);

namespace Bookando\Tests\Support\Resources;

final class ResourceFactory
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function make(array $overrides = []): array
    {
        $resource = [
            'name'         => 'Trainingsraum',
            'description'  => 'Standardraum für Kurse',
            'capacity'     => 5,
            'tags'         => ['Standard'],
            'availability' => [self::slot()],
        ];

        foreach ($overrides as $key => $value) {
            if ($key === 'availability' && is_array($value)) {
                $resource[$key] = $value;
                continue;
            }

            $resource[$key] = $value;
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function slot(array $overrides = []): array
    {
        $slot = [
            'id'       => null,
            'date'     => '2025-01-02',
            'start'    => '08:00',
            'end'      => '10:00',
            'capacity' => 3,
            'notes'    => 'Standard-Slot',
        ];

        foreach ($overrides as $key => $value) {
            $slot[$key] = $value;
        }

        return $slot;
    }
}
