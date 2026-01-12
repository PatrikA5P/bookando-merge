<?php
// scripts/export-license-map.php

// Konfiguration – passe ggf. Pfade an:
$modulesDir = __DIR__ . '/../src/modules/';
$outputPhp  = __DIR__ . '/../src/Core/Licensing/license-features.php';
$outputJson = __DIR__ . '/../src/Core/Licensing/license-features.json';

$plans = [];
$modules = [];
$features = [];

foreach (scandir($modulesDir) as $slug) {
    if ($slug === '.' || $slug === '..' || !is_dir($modulesDir . $slug)) continue;
    $manifestFile = $modulesDir . $slug . '/module.json';
    if (!file_exists($manifestFile)) continue;

    $manifest = json_decode(file_get_contents($manifestFile), true);

    // Zuordnung: Plan → Module
    $plan = $manifest['plan'] ?? 'starter';
    $plans[$plan][] = $slug;

    // Zuordnung: Modul → Features (aus features_required)
    $modules[$slug] = [
        'plan' => $plan,
        'features_required' => $manifest['features_required'] ?? [],
        'license_required'  => $manifest['license_required'] ?? false,
        'group'             => $manifest['group'] ?? '',
        'always_active'     => $manifest['always_active'] ?? false,
        'visible'           => $manifest['visible'] ?? true,
    ];

    // Alle Features sammeln
    foreach ($manifest['features_required'] ?? [] as $f) {
        $features[$f][] = $slug;
    }
}

// PHP-Array für direkte Nutzung im LicenseManager:
$phpContent = "<?php\nreturn " . var_export([
    'plans'   => $plans,
    'modules' => $modules,
    'features'=> $features,
], true) . ";\n";

file_put_contents($outputPhp, $phpContent);
file_put_contents($outputJson, json_encode([
    'plans'   => $plans,
    'modules' => $modules,
    'features'=> $features,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "✅ Export abgeschlossen: $outputPhp und $outputJson\n";
