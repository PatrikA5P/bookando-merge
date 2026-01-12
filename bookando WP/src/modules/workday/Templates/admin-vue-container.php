<?php
/**
 * Admin-Container für Workday-Modul
 *
 * @package Bookando
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$moduleSlug = 'workday';
$moduleData = [
    'rest_base' => 'workday',
];

require BOOKANDO_PLUGIN_DIR . 'src/Core/Admin/vue-container.php';
