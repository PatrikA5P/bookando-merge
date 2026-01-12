<?php

if (is_admin()) {
    wp_enqueue_media();
}

$moduleSlug = 'settings';
$moduleData = [
    'rest_base' => 'settings',
];

require BOOKANDO_PLUGIN_DIR . 'src/Core/Admin/vue-container.php';
