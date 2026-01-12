<?php
// scripts/doctor.php

define('BOOKANDO_MODULES_DIR', __DIR__ . '/../src/modules/');

echo "📋 Bookando Modulprüfung\n\n";

foreach (scandir(BOOKANDO_MODULES_DIR) as $slug) {
    if ($slug === '.' || $slug === '..') continue;

    $path = BOOKANDO_MODULES_DIR . '/' . $slug;
    if (!is_dir($path)) continue;

    echo "🔍 Modul: $slug\n";

    // module.json vorhanden?
    $manifest = "$path/module.json";
    if (!file_exists($manifest)) {
        echo "  ❌ Fehlende Datei: module.json\n";
        continue;
    }

    $json = json_decode(file_get_contents($manifest), true);
    if (!isset($json['slug']) || strtolower($json['slug']) !== strtolower($slug)) {
        echo "  ⚠️ Slug in module.json stimmt nicht mit Verzeichnis überein\n";
    }

    // Lizenz/Feature-Infos anzeigen
    $plan = $json['plan'] ?? 'starter';
    $features = isset($json['features_required']) ? implode(', ', $json['features_required']) : '—';
    $license = $json['license_required'] ?? false;
    echo "    Lizenzplan: $plan\n";
    echo "    Lizenzpflicht: " . ($license ? '✅' : '❌') . "\n";
    echo "    Features: $features\n";

    // Module.php vorhanden?
    $moduleFile = "$path/Module.php";
    if (!file_exists($moduleFile)) {
        echo "  ❌ Fehlende Datei: Module.php\n";
        continue;
    }

    // Klasse vorhanden?
    $fqcn = "Bookando\\Modules\\$slug\\Module";
    if (!class_exists($fqcn)) {
        require_once $moduleFile;
    }

    if (!class_exists($fqcn)) {
        echo "  ❌ Klasse $fqcn nicht gefunden\n";
        continue;
    }

    if (!is_subclass_of($fqcn, 'Bookando\Core\Base\BaseModule')) {
        echo "  ⚠️ Klasse $fqcn erweitert nicht Bookando\\Core\\Base\\BaseModule\n";
    } else {
        echo "  ✅ Modulklasse OK\n";
    }

    echo "\n";
}
