<?php
/**
 * Script zum Zurücksetzen der Academy-Daten
 *
 * Führen Sie dieses Script aus, um die Academy-Daten zurückzusetzen
 * und die neue Datenstruktur mit main_topics zu verwenden.
 *
 * ACHTUNG: Dieses Script löscht ALLE bestehenden Kurse und Ausbildungskarten!
 *
 * Aufruf: wp eval-file scripts/reset-academy-data.php
 */

if (!defined('ABSPATH')) {
    // Wenn nicht in WordPress-Kontext, versuche wp-load.php zu laden
    $wp_load = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        die("Fehler: WordPress-Umgebung konnte nicht geladen werden.\n");
    }
}

echo "========================================\n";
echo "Academy-Daten Zurücksetzen\n";
echo "========================================\n\n";

// Hole aktuelle Daten
$current_state = get_option('bookando_academy_state', []);

if (empty($current_state)) {
    echo "✓ Keine Daten vorhanden - State ist leer.\n";
} else {
    echo "Aktuelle Daten:\n";
    echo "- Kurse: " . count($current_state['courses'] ?? []) . "\n";
    echo "- Ausbildungskarten: " . count($current_state['training_cards'] ?? []) . "\n\n";
}

echo "Lösche alte Daten...\n";
delete_option('bookando_academy_state');

echo "✓ Alte Daten wurden gelöscht.\n\n";

echo "Neue Daten werden beim nächsten Laden des Academy-Moduls automatisch erstellt.\n";
echo "Die neuen Defaults enthalten:\n";
echo "- 1 Beispiel-Kurs (Grundlagen Theorie)\n";
echo "- 1 Beispiel-Ausbildungskarte (Max Mustermann)\n\n";

echo "========================================\n";
echo "✓ Fertig!\n";
echo "========================================\n";
echo "\nBitte laden Sie jetzt das Academy-Modul im Browser neu.\n";
