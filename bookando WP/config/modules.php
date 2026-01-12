<?php

declare(strict_types=1);

return [
    'academy' => [
        'slug' => 'academy',
        'class' => \Bookando\Modules\Academy\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/academy/module.json',
    ],
    'appointments' => [
        'slug' => 'appointments',
        'class' => \Bookando\Modules\Appointments\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/appointments/module.json',
    ],
    'customers' => [
        'slug' => 'customers',
        'class' => \Bookando\Modules\Customers\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/customers/module.json',
    ],
    'employees' => [
        'slug' => 'employees',
        'class' => \Bookando\Modules\Employees\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/employees/module.json',
    ],
    'finance' => [
        'slug' => 'finance',
        'class' => \Bookando\Modules\Finance\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/finance/module.json',
    ],
    'offers' => [
        'slug' => 'offers',
        'class' => \Bookando\Modules\Offers\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/offers/module.json',
    ],
    'partnerhub' => [
        'slug' => 'partnerhub',
        'class' => \Bookando\Modules\Partnerhub\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/partnerhub/module.json',
    ],
    'resources' => [
        'slug' => 'resources',
        'class' => \Bookando\Modules\Resources\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/resources/module.json',
    ],
    'settings' => [
        'slug' => 'settings',
        'class' => \Bookando\Modules\Settings\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/settings/module.json',
    ],
    'tools' => [
        'slug' => 'tools',
        'class' => \Bookando\Modules\Tools\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/tools/module.json',
    ],
];
