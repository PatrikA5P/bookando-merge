<?php

declare(strict_types=1);

namespace Bookando\Modules\academy;

use function __;
use function add_action;
use function check_admin_referer;
use function current_user_can;
use function wp_die;
use function wp_redirect;
use function admin_url;
use function wp_generate_uuid4;

class AdminTemplateCreator
{
    public static function init(): void
    {
        add_action('admin_post_create_academy_templates', [self::class, 'handleCreateTemplates']);
    }

    public static function handleCreateTemplates(): void
    {
        if (!current_user_can('manage_bookando_academy')) {
            wp_die(__('Sie haben keine Berechtigung für diese Aktion.', 'bookando'));
        }

        check_admin_referer('create_academy_templates', 'template_nonce');

        $now = current_time('mysql');
        $state = StateRepository::getState();

        // Erstelle Kategorie B Kurs
        $courseB = self::getKategorieBCourse($now);
        $state['courses'][] = $courseB;

        // Erstelle Kategorie A Kurs
        $courseA = self::getKategorieACourse($now);
        $state['courses'][] = $courseA;

        // Speichere State
        StateRepository::saveState($state);

        // Redirect zurück zum Academy-Modul
        wp_redirect(
            add_query_arg(
                [
                    'page' => 'bookando-academy',
                    'templates_created' => '1'
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public static function getKategorieBCoursePublic(string $now): array
    {
        return self::getKategorieBCourse($now);
    }

    public static function getKategorieACoursePublic(string $now): array
    {
        return self::getKategorieACourse($now);
    }

    private static function getKategorieBCourse(string $now): array
    {
        return [
            'title' => 'Fahrausbildung Kategorie B (PKW)',
            'description' => 'Vollständige praktische Fahrausbildung für die Führerscheinkategorie B - vom ersten Kennenlernen des Fahrzeugs bis zur Prüfungsreife.',
            'course_type' => 'physical',
            'author' => 'Fahrschule',
            'max_participants' => 1,
            'visibility' => 'private',
            'display_from' => null,
            'display_until' => null,
            'level' => 'beginner',
            'category' => 'Kategorie B',
            'tags' => ['Fahrschule', 'PKW', 'Praktische Ausbildung'],
            'featured_image' => null,
            'intro_video' => null,
            'sequential_topics' => true,
            'duration_minutes' => 0,
            'topics' => [
                [
                    'title' => 'Vorschulung',
                    'summary' => 'Erste Schritte - Vorbereitung und grundlegende Fahrzeugkenntnisse',
                    'lessons' => [
                        ['title' => 'Rundumkontrolle Vorbereitung im Stand', 'content' => 'Überprüfung des Fahrzeugs vor Fahrtantritt: Reifen, Lichter, Spiegel, Sicherheitsgurte.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Sitzhaltung & Spiegeleinstellung', 'content' => 'Korrekte Sitzposition einstellen, alle Spiegel optimal anpassen für beste Rundumsicht.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Bedienelemente kennenlernen', 'content' => 'Lenkrad, Pedalen, Schalthebel, Blinker, Licht, Scheibenwischer und weitere Bedienelemente.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Anfahren & Lenken', 'content' => 'Sanftes Anfahren und erste Lenkbewegungen auf verkehrsarmem Platz.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Anhalten & Bremsen', 'content' => 'Kontrolliertes Bremsen und Anhalten an definierter Stelle.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Gangwechsel', 'content' => 'Schalten zwischen den Gängen, Kupplung richtig nutzen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Beobachtung des Umfeldes', 'content' => 'Blicktechnik, Schulterblick, Spiegel regelmäßig kontrollieren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren auf gerader Strecke', 'content' => 'Geradeausfahrt mit konstanter Geschwindigkeit, Spurhaltung üben.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Grundschulung',
                    'summary' => 'Verkehrsteilnahme - erste Erfahrungen im Straßenverkehr',
                    'lessons' => [
                        ['title' => 'Einordnen und Spurwechsel', 'content' => 'Sicheres Wechseln der Fahrspur, Blinken, Schulterblick, Einfädeln.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Vortritt beachten', 'content' => 'Rechts-vor-Links, Hauptstraßen, Stoppschilder, Lichtsignalanlagen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Geschwindigkeit anpassen', 'content' => 'Tempolimits einhalten, Geschwindigkeit der Situation anpassen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren in Kurven', 'content' => 'Kurventechnik: Bremsen vor der Kurve, in der Kurve lenken und beschleunigen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Kreuzungen und Verzweigungen', 'content' => 'Sicheres Überqueren von Kreuzungen, richtige Einordnung.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Kreisverkehr', 'content' => 'Einfahren, Verhalten im Kreisel, korrekt Ausfahren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verkehrszeichen und Markierungen', 'content' => 'Bedeutung und Beachtung aller wichtigen Verkehrszeichen.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Hauptschulung',
                    'summary' => 'Routinebildung - sicheres Fahren in verschiedenen Situationen',
                    'lessons' => [
                        ['title' => 'Abbiegen links und rechts', 'content' => 'Abbiegen mit korrekter Einordnung, Blinken, Rücksicht auf Fussgänger.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Überholen', 'content' => 'Wann und wie überholen? Sicherheitsabstand, Blinken, Spurwechsel.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren in verkehrsreichen Situationen', 'content' => 'Stadtverkehr, Stau, dichter Verkehr - Ruhe bewahren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Parken längs und quer', 'content' => 'Einparken in Längs- und Querparklücken, Rückwärtsfahren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Anfahren in Steigung', 'content' => 'Bergauf anfahren ohne zurückzurollen, Handbremse nutzen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verkehrssituationen mit Fussgängern', 'content' => 'Zebrastreifen, Schulwege, besondere Vorsicht.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verkehrssituationen mit Radfahrern', 'content' => 'Abstand zu Radfahrern, Vorsicht beim Abbiegen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verhalten auf Nebenstrassen', 'content' => 'Begegnungsverkehr auf engen Strassen, Vortrittsregeln.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Perfektionsschulung',
                    'summary' => 'Prüfungsvorbereitung - anspruchsvolle Situationen meistern',
                    'lessons' => [
                        ['title' => 'Fahren nach Wegweisern', 'content' => 'Navigation, Orientierung, vorausschauend richtige Spur wählen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren auf Autobahn und Autostrassen', 'content' => 'Auffahren, hohe Geschwindigkeiten, Spurwechsel, Abfahren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren bei Nacht und schlechter Sicht', 'content' => 'Lichtführung, reduzierte Sicht, Nebel, Regen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Schwierige Verkehrspartner / -situationen', 'content' => 'Umgang mit aggressiven Fahrern, unübersichtlichen Kreuzungen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Defensive und vorausschauende Fahrweise', 'content' => 'Gefahren frühzeitig erkennen, Sicherheitsabstand, Bremsbereitschaft.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Fahrmanöver',
                    'summary' => 'Prüfungsrelevante Manöver perfekt beherrschen',
                    'lessons' => [
                        ['title' => 'Wenden auf der Strasse', 'content' => 'Drehen auf engem Raum, Sicherheit, Beobachtung.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Rückwärtsfahren (gerade & Kurve)', 'content' => 'Rückwärts geradeaus und in Kurven, Orientierung.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Notbremsung / Vollbremsung', 'content' => 'Schnellstmöglich zum Stehen kommen, ABS nutzen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Ausweichmanöver', 'content' => 'Hindernis umfahren ohne zu bremsen, Stabilität halten.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
            ],
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private static function getKategorieACourse(string $now): array
    {
        return [
            'title' => 'Fahrausbildung Kategorie A (Motorrad)',
            'description' => 'Vollständige praktische Motorradausbildung für die Führerscheinkategorie A - vom Aufsteigen bis zur perfekten Kurvenlage.',
            'course_type' => 'physical',
            'author' => 'Fahrschule',
            'max_participants' => 1,
            'visibility' => 'private',
            'display_from' => null,
            'display_until' => null,
            'level' => 'beginner',
            'category' => 'Kategorie A',
            'tags' => ['Fahrschule', 'Motorrad', 'Praktische Ausbildung'],
            'featured_image' => null,
            'intro_video' => null,
            'sequential_topics' => true,
            'duration_minutes' => 0,
            'topics' => [
                [
                    'title' => 'Vorschulung',
                    'summary' => 'Erste Schritte - Motorrad kennenlernen und Grundlagen',
                    'lessons' => [
                        ['title' => 'Rundumkontrolle Vorbereitung im Stand', 'content' => 'Überprüfung des Motorrads: Reifen, Bremsen, Kette, Beleuchtung, Ölstand.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Anfahren und Anhalten in der Ebene (manuelle Getriebe)', 'content' => 'Erstes Anfahren und sicheres Anhalten auf ebener Strecke mit manuellem Getriebe.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Blicktechnik und Lenken', 'content' => 'Richtige Blickführung beim Motorradfahren, sanfte Lenkbewegungen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Schalten aller Gänge (manuelle Getriebe)', 'content' => 'Schalten hoch und runter durch alle Gänge, Kupplung fein dosieren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Bremsen', 'content' => 'Beide Bremsen richtig einsetzen, dosiert und sicher bremsen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Abbiegen rechts und links / zeitliche Faktoren', 'content' => 'Abbiegevorgänge rechts und links, zeitliche Einschätzung und Planung.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahrzeugbedienung beim Rückwärtsfahren', 'content' => 'Motorrad schiebend rückwärts bewegen, lenken und manövrieren.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Grundschulung',
                    'summary' => 'Verkehrsteilnahme - sicheres Fahren im Straßenverkehr',
                    'lessons' => [
                        ['title' => 'Grundlagen Blickführung', 'content' => 'Fundamentale Blicktechniken für sicheres Motorradfahren im Verkehr.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Abbiegen / Blicktechnik an Verzweigungen', 'content' => 'Richtiges Abbiegen mit korrekter Blickführung an Kreuzungen und Verzweigungen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Bremsbereitschaft / Sichtpunktfahren', 'content' => 'Vorausschauendes Fahren, Bremsbereitschaft und Fahren nach Sichtweite.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahrbahn Benützung', 'content' => 'Korrekte Nutzung der Fahrbahn, Spurpositionierung.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Kreisverkehrsplatz', 'content' => 'Einfahren, Verhalten und Ausfahren im Kreisverkehr mit dem Motorrad.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Benützung von Fahrstreifen, Einspurstrecken, Radstreifen', 'content' => 'Richtiger Umgang mit verschiedenen Fahrbahnmarkierungen und -arten.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verhalten bei Lichtsignalen', 'content' => 'Ampeln und Lichtsignalanlagen richtig beachten und darauf reagieren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Einfügen im Verkehr', 'content' => 'Sicheres Einfädeln und Eingliedern in den fließenden Verkehr.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verkehr in Steigungen und Gefällen', 'content' => 'Fahren bergauf und bergab, Gang- und Geschwindigkeitswahl.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Hauptschulung',
                    'summary' => 'Routinebildung - fortgeschrittene Fahrtechnik',
                    'lessons' => [
                        ['title' => 'Vortritt', 'content' => 'Vortrittsregeln verstehen und korrekt anwenden.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verkehrspartner / 3-A-Training', 'content' => 'Interaktion mit anderen Verkehrsteilnehmern, 3-A-Training (Alter, Absicht, Aufmerksamkeit).', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahrstreifen und Spurwechsel', 'content' => 'Sichere Spurwechsel, Blinken, Schulterblick beim Motorradfahren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Überholen und Vorbeifahren', 'content' => 'Sicheres Überholen und Vorbeifahren, Beschleunigung, Sicherheitsabstand.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Verhalten gegenüber ÖV und Bahnübergänge', 'content' => 'Richtiges Verhalten bei öffentlichen Verkehrsmitteln und Bahnübergängen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Mithalten / Abstände / Kolonnenfahren', 'content' => 'Fahren in Kolonnen, Sicherheitsabstände einhalten, Geschwindigkeit anpassen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren auf besonderen Strassen', 'content' => 'Besondere Fahrsituationen auf Bergstrassen, engen Strassen und Serpentinen.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Perfektionsschulung',
                    'summary' => 'Prüfungsvorbereitung - anspruchsvolle Situationen meistern',
                    'lessons' => [
                        ['title' => 'Fahren nach Wegweisern', 'content' => 'Navigation, Orientierung, vorausschauend richtige Spur wählen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren auf Autobahn und Autostrassen', 'content' => 'Hohe Geschwindigkeiten, Windempfindlichkeit, sichere Spurwechsel auf Autobahnen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Fahren bei Nacht und schlechter Sicht', 'content' => 'Lichtführung, eingeschränkte Sicht, Vorsicht bei Nebel und Regen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Schwierige Verkehrspartner / -situationen', 'content' => 'Defensive Fahrweise, Gefahren antizipieren und richtig reagieren.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
                [
                    'title' => 'Fahrmanöver',
                    'summary' => 'Prüfungsrelevante Manöver perfekt beherrschen',
                    'lessons' => [
                        ['title' => 'Sichern des Fahrzeuges in Steigung und Gefälle', 'content' => 'Motorrad sicher abstellen und sichern in Steigungen und Gefällen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Rückwärtsfahren', 'content' => 'Motorrad kontrolliert rückwärts bewegen und rangieren.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Wenden', 'content' => 'Motorrad auf engem Raum wenden und drehen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Parkieren rechtwinklig vorwärts', 'content' => 'Vorwärts in rechtwinklige Parkplätze einparken.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Parkieren rechtwinklig rückwärts', 'content' => 'Rückwärts in rechtwinklige Parkplätze einparken.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Parkieren seitwärts', 'content' => 'Seitwärts einparken zwischen anderen Fahrzeugen.', 'images' => [], 'videos' => [], 'files' => []],
                        ['title' => 'Schnelle sichere Bremsung / Notbremsung', 'content' => 'Beide Bremsen voll nutzen, ABS, schnellstmöglich und sicher stoppen.', 'images' => [], 'videos' => [], 'files' => []],
                    ],
                    'quizzes' => []
                ],
            ],
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
