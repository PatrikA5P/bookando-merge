<?php
// scripts/query-license.php

$map = include __DIR__ . '/../src/Core/Licensing/license-features.php';

$argv = $_SERVER['argv'];
$cmd = $argv[1] ?? null;
$key = $argv[2] ?? null;

if (!$cmd) {
    echo "Usage: php query-license.php [plan|module|feature] <name>\n";
    exit(1);
}

if ($cmd === 'plan') {
    // php scripts/query-license.php plan starter
    if (!$key || !isset($map['plans'][$key])) {
        echo "Verfügbare Pläne: " . implode(', ', array_keys($map['plans'])) . "\n";
        exit(1);
    }
    echo "Module für Plan $key:\n";
    print_r($map['plans'][$key]);
}
elseif ($cmd === 'module') {
    // php scripts/query-license.php module customers
    if (!$key || !isset($map['modules'][$key])) {
        echo "Verfügbare Module: " . implode(', ', array_keys($map['modules'])) . "\n";
        exit(1);
    }
    print_r($map['modules'][$key]);
}
elseif ($cmd === 'feature') {
    // php scripts/query-license.php feature export_csv
    if (!$key || !isset($map['features'][$key])) {
        echo "Verfügbare Features: " . implode(', ', array_keys($map['features'])) . "\n";
        exit(1);
    }
    echo "Module mit Feature $key:\n";
    print_r($map['features'][$key]);
}
else {
    echo "Unbekanntes Kommando.\n";
    exit(1);
}
