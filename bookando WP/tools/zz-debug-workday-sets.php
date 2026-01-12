<?php
declare(strict_types=1);

if (!defined('WP_CLI')) {
    fwrite(STDERR, "This helper can only be executed via WP-CLI.\n");
    exit(1);
}

const DEBUG_TOKEN = 'aQ1234';

/** @var array<string,mixed> $assoc_args */
$assoc_args = isset($assoc_args) && is_array($assoc_args) ? $assoc_args : [];

$token = (string)($assoc_args['token'] ?? '');
if ($token !== DEBUG_TOKEN) {
    WP_CLI::error('Forbidden: missing or invalid --token parameter.');
}

$userId = isset($assoc_args['user_id']) ? (int)$assoc_args['user_id'] : 0;
if ($userId <= 0) {
    WP_CLI::error('Missing or invalid --user_id parameter.');
}

global $wpdb;

$prefix  = $wpdb->prefix;
$tabSet  = $prefix . 'bookando_employees_workday_sets';
$tabInt  = $prefix . 'bookando_employees_workday_intervals';

$out = [
    'ok'        => true,
    'input'     => ['user_id' => $userId],
    'tables'    => [
        'prefix'       => $prefix,
        'tab_set'      => $tabSet,
        'tab_int'      => $tabInt,
        'table_exists' => false,
    ],
    'raw_probe' => [
        'count_sql'   => null,
        'count_rows'  => 0,
        'count_error' => null,
    ],
    'sets_sql'         => null,
    'sets_found'       => 0,
    'sets_error'       => null,
    'intervals_sql'    => null,
    'intervals_found'  => 0,
    'intervals_error'  => null,
    'sets'             => [],
];

$exists = $wpdb->get_var($wpdb->prepare(
    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s',
    $tabSet
));
$out['tables']['table_exists'] = (bool) $exists;

if (!$exists) {
    $out['ok'] = false;
    $out['error'] = "Table {$tabSet} not found";
    WP_CLI::line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    WP_CLI::halt(1);
}

$out['raw_probe']['count_sql'] = $wpdb->prepare(
    'SELECT COUNT(*) FROM ' . $tabSet . ' WHERE user_id=%d',
    $userId
);
$out['raw_probe']['count_rows']  = (int) $wpdb->get_var($out['raw_probe']['count_sql']);
$out['raw_probe']['count_error'] = (string) $wpdb->last_error;

$out['sets_sql'] = $wpdb->prepare(
    'SELECT id, user_id, week_day_id, location_id, service_id, label, sort, created_at, updated_at
     FROM ' . $tabSet . '
     WHERE user_id=%d
     ORDER BY week_day_id ASC, sort ASC, id ASC',
    $userId
);
$sets = $wpdb->get_results($out['sets_sql'], ARRAY_A) ?: [];
$out['sets_error'] = (string) $wpdb->last_error;
$out['sets_found'] = count($sets);

$intervalsBySet = [];
if (!empty($sets)) {
    $setIds = array_map(static fn(array $row): int => (int) $row['id'], $sets);
    $placeholders = implode(',', array_fill(0, count($setIds), '%d'));
    $sqlInt = 'SELECT id, set_id, start_time, end_time, is_break, created_at, updated_at
               FROM ' . $tabInt . '
               WHERE set_id IN (' . $placeholders . ')
               ORDER BY start_time ASC';

    $args = array_merge([$sqlInt], $setIds);
    $out['intervals_sql'] = call_user_func_array([$wpdb, 'prepare'], $args);

    $intervals = $wpdb->get_results($out['intervals_sql'], ARRAY_A) ?: [];
    $out['intervals_error'] = (string) $wpdb->last_error;
    $out['intervals_found'] = count($intervals);

    foreach ($intervals as $row) {
        $sid = (int) $row['set_id'];
        $intervalsBySet[$sid][] = [
            'id'         => (int) $row['id'],
            'set_id'     => $sid,
            'start_time' => (string) $row['start_time'],
            'end_time'   => (string) $row['end_time'],
            'is_break'   => (int) $row['is_break'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}

foreach ($sets as &$set) {
    $sid = (int) $set['id'];
    $set['week_day_id'] = (int) $set['week_day_id'];
    $set['location_id'] = isset($set['location_id']) ? (int) $set['location_id'] : null;
    $set['service_id']  = isset($set['service_id']) ? (int) $set['service_id'] : null;
    $set['sort']        = (int) $set['sort'];
    $set['intervals']   = $intervalsBySet[$sid] ?? [];
}
unset($set);

$out['sets'] = $sets;

WP_CLI::line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
