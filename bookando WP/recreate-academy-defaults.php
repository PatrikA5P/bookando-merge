<?php
/**
 * Manueller Trigger zum Neuerstellen der Academy-Defaults
 * Rufe diese Datei über den Browser auf: http://bookando-site.local/wp-content/plugins/bookando/recreate-academy-defaults.php
 */

// WordPress Bootstrap
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('WordPress nicht gefunden. Pfad: ' . $wp_load_path);
}
require_once $wp_load_path;

// Prüfe Berechtigungen
if (!current_user_can('manage_options')) {
    die('Keine Berechtigung!');
}

echo "<h1>Academy Defaults neu erstellen</h1>";
echo "<pre>";

global $wpdb;
$prefix = $wpdb->prefix . 'bookando_academy_';

// 1. Lösche alle bestehenden Daten
echo "1. Lösche bestehende Daten...\n";
$wpdb->query("DELETE FROM {$prefix}courses");
$wpdb->query("DELETE FROM {$prefix}training_cards");
echo "   Kurse gelöscht: " . $wpdb->rows_affected . "\n";
echo "   Training Cards gelöscht\n\n";

// 2. Setze Migration-Flag zurück
echo "2. Setze Migration-Flag zurück...\n";
delete_option('bookando_academy_migrated');
delete_option('bookando_academy_state');
echo "   Migration-Flag zurückgesetzt\n\n";

// 3. Erstelle neue Defaults
echo "3. Erstelle neue Defaults...\n";
require_once __DIR__ . '/src/modules/academy/StateRepository.php';
$state = \Bookando\Modules\academy\StateRepository::getState();

echo "   Kurse erstellt: " . count($state['courses']) . "\n";
echo "   Training Cards erstellt: " . count($state['training_cards']) . "\n\n";

// 4. Überprüfe Topics
echo "4. Überprüfe Topics für jeden Kurs:\n";
foreach ($state['courses'] as $course) {
    echo "   Kurs ID {$course['id']}: {$course['title']}\n";
    echo "     - Topics: " . count($course['topics'] ?? []) . "\n";

    if (!empty($course['topics'])) {
        $firstTopic = $course['topics'][0];
        echo "     - Erstes Topic: {$firstTopic['title']}\n";
        echo "     - Lessons im ersten Topic: " . count($firstTopic['lessons'] ?? []) . "\n";
    }

    // Überprüfe in der Datenbank
    $dbTopics = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$prefix}topics WHERE course_id = %d",
        $course['id']
    ));
    echo "     - Topics in DB: " . $dbTopics . "\n\n";
}

// 5. Überprüfe Training Card Topics
echo "5. Überprüfe Training Card Topics:\n";
foreach ($state['training_cards'] as $card) {
    echo "   Card ID {$card['id']}: {$card['student']} - {$card['program']}\n";
    echo "     - Main Topics: " . count($card['main_topics'] ?? []) . "\n";

    if (!empty($card['main_topics'])) {
        $firstTopic = $card['main_topics'][0];
        echo "     - Erstes Topic: {$firstTopic['title']}\n";
        echo "     - Lessons im ersten Topic: " . count($firstTopic['lessons'] ?? []) . "\n";
    }

    // Überprüfe in der Datenbank
    $dbTopics = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$prefix}training_topics WHERE card_id = %d",
        $card['id']
    ));
    echo "     - Topics in DB: " . $dbTopics . "\n\n";
}

echo "</pre>";
echo "<h2>✅ Fertig!</h2>";
echo "<p><a href='/wp-admin/admin.php?page=bookando_academy'>Zurück zum Academy-Modul</a></p>";
