<?php

declare(strict_types=1);

namespace Bookando\Modules\academy;

use Bookando\Modules\academy\Models\CourseModel;
use Bookando\Modules\academy\Models\TrainingCardModel;

/**
 * StateRepository für Academy-Modul.
 *
 * Neu: Verwendet eigene Datenbanktabellen statt wp_options.
 * Alte wp_options-Daten werden automatisch bei erster Verwendung migriert.
 */
class StateRepository
{
    private static ?CourseModel $courseModel = null;
    private static ?TrainingCardModel $cardModel = null;

    /**
     * Lazy-Load CourseModel.
     */
    protected static function courseModel(): CourseModel
    {
        if (self::$courseModel === null) {
            self::$courseModel = new CourseModel();
        }
        return self::$courseModel;
    }

    /**
     * Lazy-Load TrainingCardModel.
     */
    protected static function cardModel(): TrainingCardModel
    {
        if (self::$cardModel === null) {
            self::$cardModel = new TrainingCardModel();
        }
        return self::$cardModel;
    }

    /**
     * Vollständigen State laden (Courses + Training Cards).
     */
    public static function getState(): array
    {
        // Auto-Migration beim ersten Aufruf
        self::migrateFromWpOptions();

        return [
            'courses' => self::courseModel()->all(),
            'training_cards' => self::cardModel()->all(),
        ];
    }

    /**
     * Legacy: saveState (macht nichts mehr, da wir direkte CRUD-Operationen haben).
     * @deprecated Use upsertCourse() or upsertTrainingCard() instead
     */
    public static function saveState(array $state): void
    {
        // No-op: Daten werden direkt über Models gespeichert
    }

    // =========================================================================
    // Course Operations
    // =========================================================================

    /**
     * Kurs erstellen oder aktualisieren.
     */
    public static function upsertCourse(array $payload): array
    {
        $courseId = self::courseModel()->save($payload);
        $course = self::courseModel()->find($courseId);
        return $course ?: [];
    }

    /**
     * Kurs löschen.
     */
    public static function deleteCourse(string $id): bool
    {
        // Validiere und konvertiere ID
        $numericId = filter_var($id, FILTER_VALIDATE_INT);
        if ($numericId === false) {
            error_log('[Bookando Academy] Invalid course ID for deletion: ' . $id);
            return false;
        }

        $result = self::courseModel()->delete($numericId);
        error_log('[Bookando Academy] Delete course ' . $numericId . ': ' . ($result ? 'success' : 'failed'));
        return $result;
    }

    /**
     * Alle Kurse laden.
     */
    public static function getAllCourses(): array
    {
        return self::courseModel()->all();
    }

    /**
     * Kurs per ID laden.
     */
    public static function getCourse(int $id): ?array
    {
        return self::courseModel()->find($id);
    }

    // =========================================================================
    // Training Card Operations
    // =========================================================================

    /**
     * Ausbildungskarte erstellen oder aktualisieren.
     */
    public static function upsertTrainingCard(array $payload): array
    {
        $cardId = self::cardModel()->save($payload);
        $card = self::cardModel()->find($cardId);
        return $card ?: [];
    }

    /**
     * Ausbildungskarte löschen.
     */
    public static function deleteTrainingCard(string $id): bool
    {
        // Validiere und konvertiere ID
        $numericId = filter_var($id, FILTER_VALIDATE_INT);
        if ($numericId === false) {
            error_log('[Bookando Academy] Invalid training card ID for deletion: ' . $id);
            return false;
        }

        $result = self::cardModel()->delete($numericId);
        error_log('[Bookando Academy] Delete training card ' . $numericId . ': ' . ($result ? 'success' : 'failed'));
        return $result;
    }

    /**
     * Progress aktualisieren.
     */
    public static function updateTrainingProgress(string $id, float $progress): bool
    {
        return self::cardModel()->updateProgress((int)$id, $progress);
    }

    /**
     * Alle Ausbildungskarten laden.
     */
    public static function getAllTrainingCards(): array
    {
        return self::cardModel()->all();
    }

    /**
     * Ausbildungskarte per ID laden.
     */
    public static function getTrainingCard(int $id): ?array
    {
        return self::cardModel()->find($id);
    }

    // =========================================================================
    // Migration & Defaults
    // =========================================================================

    /**
     * Migriert bestehende Daten aus wp_options in die neuen Tabellen.
     * Wird automatisch beim ersten Laden ausgeführt.
     */
    protected static function migrateFromWpOptions(): void
    {
        // Prüfe ob Migration bereits durchgeführt wurde
        if (get_option('bookando_academy_migrated', false)) {
            return;
        }

        $oldData = get_option('bookando_academy_state', null);

        if ($oldData === null) {
            // Keine alten Daten vorhanden -> erstelle Defaults
            self::createDefaults();
            update_option('bookando_academy_migrated', true, false);
            return;
        }

        // Alte Daten vorhanden -> migrieren
        if (!is_array($oldData)) {
            $oldData = [];
        }

        // Migriere Kurse
        $courses = $oldData['courses'] ?? [];
        foreach ($courses as $course) {
            try {
                self::courseModel()->save($course);
            } catch (\Exception $e) {
                error_log('[Bookando Academy] Failed to migrate course: ' . $e->getMessage());
            }
        }

        // Migriere Ausbildungskarten
        $cards = $oldData['training_cards'] ?? [];
        foreach ($cards as $card) {
            try {
                self::cardModel()->save($card);
            } catch (\Exception $e) {
                error_log('[Bookando Academy] Failed to migrate training card: ' . $e->getMessage());
            }
        }

        // Markiere Migration als abgeschlossen
        update_option('bookando_academy_migrated', true, false);

        // Optional: Alte Daten aus wp_options löschen
        // delete_option('bookando_academy_state');
    }

    /**
     * Erstellt Default-Daten (Kategorie A & B Kurse + Beispiel-Ausbildungskarte).
     */
    protected static function createDefaults(): void
    {
        $now = current_time('mysql');

        // Lade vollständige Kurse für Kategorie A und B
        require_once __DIR__ . '/AdminTemplateCreator.php';
        $courseB = AdminTemplateCreator::getKategorieBCoursePublic($now);
        $courseA = AdminTemplateCreator::getKategorieACoursePublic($now);

        // Speichere Kurse
        self::courseModel()->save($courseB);
        self::courseModel()->save($courseA);

        // Erstelle Ausbildungskarte für Kategorie B mit allen Themen und Lektionen
        $trainingCardB = self::getKategorieBTrainingCard($now);
        self::cardModel()->save($trainingCardB);

        // Erstelle Ausbildungskarte für Kategorie A mit allen Themen und Lektionen
        $trainingCardA = self::getKategorieATrainingCard($now);
        self::cardModel()->save($trainingCardA);
    }

    /**
     * Erstellt eine Ausbildungskarte für Kategorie B mit allen Themen und Lektionen.
     */
    private static function getKategorieBTrainingCard(string $now): array
    {
        return [
            'student' => 'Max Mustermann',
            'instructor' => 'Anna Beispiel',
            'program' => __('Fahrausbildung Kategorie B', 'bookando'),
            'category' => 'B',
            'progress' => 0,
            'notes' => __('Ausbildung gestartet', 'bookando'),
            'milestones' => [],
            'main_topics' => [
                [
                    'title' => 'Vorschulung',
                    'lessons' => [
                        ['title' => 'Rundumkontrolle Vorbereitung im Stand', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Sitzhaltung & Spiegeleinstellung', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Bedienelemente kennenlernen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Anfahren & Lenken', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Anhalten & Bremsen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Gangwechsel', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Beobachtung des Umfeldes', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren auf gerader Strecke', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Grundschulung',
                    'lessons' => [
                        ['title' => 'Einordnen und Spurwechsel', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Vortritt beachten', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Geschwindigkeit anpassen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren in Kurven', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Kreuzungen und Verzweigungen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Kreisverkehr', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verkehrszeichen und Markierungen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Hauptschulung',
                    'lessons' => [
                        ['title' => 'Abbiegen links und rechts', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Überholen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren in verkehrsreichen Situationen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Parken längs und quer', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Anfahren in Steigung', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verkehrssituationen mit Fussgängern', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verkehrssituationen mit Radfahrern', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verhalten auf Nebenstrassen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Perfektionsschulung',
                    'lessons' => [
                        ['title' => 'Fahren nach Wegweisern', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren auf Autobahn und Autostrassen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren bei Nacht und schlechter Sicht', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Schwierige Verkehrspartner / -situationen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Defensive und vorausschauende Fahrweise', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Fahrmanöver',
                    'lessons' => [
                        ['title' => 'Wenden auf der Strasse', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Rückwärtsfahren (gerade & Kurve)', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Notbremsung / Vollbremsung', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Ausweichmanöver', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
            ],
        ];
    }

    /**
     * Erstellt eine Ausbildungskarte für Kategorie A mit allen Themen und Lektionen.
     */
    private static function getKategorieATrainingCard(string $now): array
    {
        return [
            'student' => 'Maria Musterfrau',
            'instructor' => 'Thomas Lehrer',
            'program' => __('Fahrausbildung Kategorie A', 'bookando'),
            'category' => 'A',
            'progress' => 0,
            'notes' => __('Ausbildung gestartet', 'bookando'),
            'milestones' => [],
            'main_topics' => [
                [
                    'title' => 'Vorschulung',
                    'lessons' => [
                        ['title' => 'Rundumkontrolle Vorbereitung im Stand', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Anfahren und Anhalten in der Ebene (manuelle Getriebe)', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Blicktechnik und Lenken', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Schalten aller Gänge (manuelle Getriebe)', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Bremsen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Abbiegen rechts und links / zeitliche Faktoren', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahrzeugbedienung beim Rückwärtsfahren', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Grundschulung',
                    'lessons' => [
                        ['title' => 'Grundlagen Blickführung', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Abbiegen / Blicktechnik an Verzweigungen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Bremsbereitschaft / Sichtpunktfahren', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahrbahn Benützung', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Kreisverkehrsplatz', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Benützung von Fahrstreifen, Einspurstrecken, Radstreifen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verhalten bei Lichtsignalen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Einfügen im Verkehr', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verkehr in Steigungen und Gefällen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Hauptschulung',
                    'lessons' => [
                        ['title' => 'Vortritt', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verkehrspartner / 3-A-Training', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahrstreifen und Spurwechsel', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Überholen und Vorbeifahren', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Verhalten gegenüber ÖV und Bahnübergänge', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Mithalten / Abstände / Kolonnenfahren', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren auf besonderen Strassen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Perfektionsschulung',
                    'lessons' => [
                        ['title' => 'Fahren nach Wegweisern', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren auf Autobahn und Autostrassen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Fahren bei Nacht und schlechter Sicht', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Schwierige Verkehrspartner / -situationen', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
                [
                    'title' => 'Fahrmanöver',
                    'lessons' => [
                        ['title' => 'Sichern des Fahrzeuges in Steigung und Gefälle', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Rückwärtsfahren', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Wenden', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Parkieren rechtwinklig vorwärts', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Parkieren rechtwinklig rückwärts', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Parkieren seitwärts', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                        ['title' => 'Schnelle sichere Bremsung / Notbremsung', 'completed' => false, 'completed_at' => null, 'notes' => '', 'resources' => []],
                    ],
                ],
            ],
        ];
    }
}
