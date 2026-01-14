<?php
/**
 * Admin-Container für Tools-Modul
 *
 * @package Bookando
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$moduleSlug = 'tools';
$moduleData = [
    'rest_base' => 'tools',
];

require BOOKANDO_PLUGIN_DIR . 'src/Core/Admin/vue-container.php';
