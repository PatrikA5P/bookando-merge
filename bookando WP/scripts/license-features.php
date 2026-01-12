<?php
/**
 * Bookando License Key Generator (CLI/Backend)
 * Speicherort: /scripts/generate-license-key.php
 * Usage: php scripts/generate-license-key.php --plan=pro --tenant=demo --expires=2026-06-01
 */

require_once __DIR__ . '/../src/Core/Licensing/license-features.php';

function uuidv4() {
    // Generate RFC 4122 compliant UUID v4
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// --- Args/CLI ---
$options = getopt('', [
    'plan:',
    'tenant::',
    'expires::',
    'site::',
    'keytype::' // uuid oder base58
]);

$plan = $options['plan'] ?? 'starter';
$tenant = $options['tenant'] ?? null;
$expires = $options['expires'] ?? date('Y-m-d', strtotime('+1 year'));
$site = $options['site'] ?? '';
$keytype = $options['keytype'] ?? 'uuid';

// --- Key-Format ---
if ($keytype === 'base58') {
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $key = '';
    for ($i = 0; $i < 22; $i++) {
        $key .= $alphabet[random_int(0, 57)];
    }
} else {
    $key = uuidv4();
}

// --- Lizenzdaten aus Mapping ---
$map = include __DIR__ . '/../src/Core/Licensing/license-features.php';
if (!isset($map[$plan])) {
    echo "❌ Plan not found in license-features.php!\n";
    exit(1);
}

function expand($arr, $map) {
    $result = [];
    foreach ($arr as $item) {
        if (is_string($item) && str_starts_with($item, '@')) {
            $parent = substr($item, 1);
            if (isset($map[$parent])) {
                $result = array_merge($result, expand($map[$parent][array_key_first($arr)], $map));
            }
        } else {
            $result[] = $item;
        }
    }
    return array_unique($result);
}
$modules = expand($map[$plan]['modules'], $map);
$features = expand($map[$plan]['features'], $map);

// --- Lizenz-Daten-Objekt ---
$data = [
    'license_key' => $key,
    'plan' => $plan,
    'modules' => $modules,
    'features' => $features,
    'site_url' => $site,
    'tenant_id' => $tenant,
    'issued_at' => gmdate('Y-m-d\TH:i:s\Z'),
    'expires_at' => date('Y-m-d\T00:00:00\Z', strtotime($expires)),
];

// --- Output ---
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Optional: in Datei speichern
// file_put_contents(__DIR__ . '/license-' . $key . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
