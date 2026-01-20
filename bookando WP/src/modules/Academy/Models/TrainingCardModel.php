<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy\Models;

class TrainingCardModel
{
    protected \wpdb $db;
    protected string $table;
    protected string $milestonesTable;
    protected string $topicsTable;
    protected string $lessonsTable;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'bookando_academy_training_cards';
        $this->milestonesTable = $wpdb->prefix . 'bookando_academy_training_milestones';
        $this->topicsTable = $wpdb->prefix . 'bookando_academy_training_topics';
        $this->lessonsTable = $wpdb->prefix . 'bookando_academy_training_lessons';
    }

    /**
     * Alle Ausbildungskarten laden (mit Topics, Lessons, Milestones).
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY created_at DESC";
        $cards = $this->db->get_results($sql, ARRAY_A) ?: [];

        foreach ($cards as &$card) {
            $cardId = (int)$card['id'];
            $card['milestones'] = $this->getMilestonesForCard($cardId);
            $card['main_topics'] = $this->getTopicsForCard($cardId);
        }

        return $cards;
    }

    /**
     * Ausbildungskarte per ID laden (mit Topics, Lessons, Milestones).
     */
    public function find(int $id): ?array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        $card = $this->db->get_row($sql, ARRAY_A);

        if (!$card) {
            return null;
        }

        $card['milestones'] = $this->getMilestonesForCard($id);
        $card['main_topics'] = $this->getTopicsForCard($id);
        return $card;
    }

    /**
     * Ausbildungskarte erstellen oder aktualisieren.
     */
    public function save(array $data): int
    {
        // Extrahiere verschachtelte Daten
        $milestones = $data['milestones'] ?? [];
        $mainTopics = $data['main_topics'] ?? [];
        unset($data['milestones'], $data['main_topics']);

        // Sanitize card data
        $cardData = $this->sanitizeCard($data);

        if (!empty($cardData['id'])) {
            // Update
            $id = (int)$cardData['id'];
            unset($cardData['id']);
            $cardData['updated_at'] = current_time('mysql');

            $this->db->update($this->table, $cardData, ['id' => $id]);

            // Lösche alte Milestones und Topics
            $this->db->delete($this->milestonesTable, ['card_id' => $id]);
            $this->db->delete($this->topicsTable, ['card_id' => $id]);
        } else {
            // Insert
            unset($cardData['id']);
            $cardData['created_at'] = current_time('mysql');
            $cardData['updated_at'] = $cardData['created_at'];

            $this->db->insert($this->table, $cardData);
            $id = (int)$this->db->insert_id;
        }

        // Milestones speichern
        foreach ($milestones as $orderIndex => $milestone) {
            $this->saveMilestone($id, $milestone, $orderIndex);
        }

        // Main Topics speichern
        foreach ($mainTopics as $orderIndex => $topic) {
            $this->saveTopic($id, $topic, $orderIndex);
        }

        return $id;
    }

    /**
     * Ausbildungskarte löschen (mit allen Topics, Lessons, Milestones via CASCADE).
     */
    public function delete(int $id): bool
    {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }

    /**
     * Progress aktualisieren.
     */
    public function updateProgress(int $id, float $progress): bool
    {
        $progress = max(0, min(1, $progress));
        return (bool)$this->db->update(
            $this->table,
            ['progress' => $progress, 'updated_at' => current_time('mysql')],
            ['id' => $id]
        );
    }

    /**
     * Milestones für eine Karte laden.
     */
    protected function getMilestonesForCard(int $cardId): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->milestonesTable} WHERE card_id = %d ORDER BY order_index ASC",
            $cardId
        );
        $milestones = $this->db->get_results($sql, ARRAY_A) ?: [];

        // Convert completed to boolean
        foreach ($milestones as &$milestone) {
            $milestone['completed'] = (bool)$milestone['completed'];
        }

        return $milestones;
    }

    /**
     * Topics für eine Karte laden.
     */
    protected function getTopicsForCard(int $cardId): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->topicsTable} WHERE card_id = %d ORDER BY order_index ASC",
            $cardId
        );
        $topics = $this->db->get_results($sql, ARRAY_A) ?: [];

        foreach ($topics as &$topic) {
            $topicId = (int)$topic['id'];
            $topic['lessons'] = $this->getLessonsForTopic($topicId);
        }

        return $topics;
    }

    /**
     * Lessons für ein Topic laden.
     */
    protected function getLessonsForTopic(int $topicId): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->lessonsTable} WHERE topic_id = %d ORDER BY order_index ASC",
            $topicId
        );
        $lessons = $this->db->get_results($sql, ARRAY_A) ?: [];

        // Convert und decode
        foreach ($lessons as &$lesson) {
            $lesson['completed'] = (bool)$lesson['completed'];
            $lesson['instructor_rating'] = isset($lesson['instructor_rating']) ? (int)$lesson['instructor_rating'] : null;
            $lesson['course_lesson_id'] = isset($lesson['course_lesson_id']) ? (int)$lesson['course_lesson_id'] : null;

            if (!empty($lesson['resources'])) {
                $lesson['resources'] = json_decode($lesson['resources'], true) ?: [];
            } else {
                $lesson['resources'] = [];
            }
        }

        return $lessons;
    }

    /**
     * Milestone speichern.
     */
    protected function saveMilestone(int $cardId, array $milestoneData, int $orderIndex): int
    {
        $milestoneData['card_id'] = $cardId;
        $milestoneData['order_index'] = $orderIndex;
        $milestoneData['title'] = sanitize_text_field($milestoneData['title'] ?? '');
        $milestoneData['completed'] = !empty($milestoneData['completed']) ? 1 : 0;
        $milestoneData['completed_at'] = !empty($milestoneData['completed_at']) ?
            sanitize_text_field($milestoneData['completed_at']) : null;
        $milestoneData['created_at'] = current_time('mysql');
        $milestoneData['updated_at'] = $milestoneData['created_at'];

        unset($milestoneData['id']); // Ignore alte IDs
        $this->db->insert($this->milestonesTable, $milestoneData);
        return (int)$this->db->insert_id;
    }

    /**
     * Topic speichern (mit Lessons).
     */
    protected function saveTopic(int $cardId, array $topicData, int $orderIndex): int
    {
        $lessons = $topicData['lessons'] ?? [];
        unset($topicData['lessons']);

        $topicData['card_id'] = $cardId;
        $topicData['order_index'] = $orderIndex;
        $topicData['title'] = sanitize_text_field($topicData['title'] ?? '');
        $topicData['created_at'] = current_time('mysql');
        $topicData['updated_at'] = $topicData['created_at'];

        unset($topicData['id']); // Ignore alte IDs
        $this->db->insert($this->topicsTable, $topicData);
        $topicId = (int)$this->db->insert_id;

        // Lessons speichern
        foreach ($lessons as $lessonIndex => $lesson) {
            $this->saveLesson($topicId, $lesson, $lessonIndex);
        }

        return $topicId;
    }

    /**
     * Lesson speichern.
     *
     * Hinweis: Training Card Lektionen werden NICHT automatisch abgerechnet.
     * Die Abrechnung erfolgt ausschließlich über gebuchte Items (Appointments, Kurse).
     * Während einer Fahrstunde können mehrere Themen/Lektionen durchgenommen werden.
     */
    protected function saveLesson(int $topicId, array $lessonData, int $orderIndex): int
    {
        $wasCompleted = !empty($lessonData['completed']);

        $lessonData['topic_id'] = $topicId;
        $lessonData['order_index'] = $orderIndex;
        $lessonData['title'] = sanitize_text_field($lessonData['title'] ?? '');
        $lessonData['completed'] = !empty($lessonData['completed']) ? 1 : 0;
        $lessonData['completed_at'] = !empty($lessonData['completed_at']) ?
            sanitize_text_field($lessonData['completed_at']) : null;

        // Bewertung und Notizen
        $lessonData['instructor_rating'] = isset($lessonData['instructor_rating']) ?
            (int)$lessonData['instructor_rating'] : null;
        $lessonData['instructor_notes'] = sanitize_textarea_field($lessonData['instructor_notes'] ?? '');
        $lessonData['student_notes'] = sanitize_textarea_field($lessonData['student_notes'] ?? '');

        // Kursverknüpfung
        $lessonData['course_lesson_id'] = isset($lessonData['course_lesson_id']) ?
            (int)$lessonData['course_lesson_id'] : null;

        // Resources (Bilder mit Annotationen, Videos, Links)
        $lessonData['resources'] = !empty($lessonData['resources']) ?
            wp_json_encode($lessonData['resources']) : '[]';

        $lessonData['created_at'] = current_time('mysql');
        $lessonData['updated_at'] = $lessonData['created_at'];

        // Setze completed_at automatisch, wenn Lektion gerade abgeschlossen wurde
        if ($wasCompleted && empty($lessonData['completed_at'])) {
            $lessonData['completed_at'] = current_time('mysql');
        }

        unset($lessonData['id']); // Ignore alte IDs
        $this->db->insert($this->lessonsTable, $lessonData);
        $lessonId = (int)$this->db->insert_id;

        return $lessonId;
    }

    /**
     * Card-Daten bereinigen.
     */
    protected function sanitizeCard(array $data): array
    {
        return [
            'id' => !empty($data['id']) ? (int)$data['id'] : null,
            'student' => sanitize_text_field($data['student'] ?? ''),
            'customer_id' => isset($data['customer_id']) ? (int)$data['customer_id'] : null,
            'instructor' => sanitize_text_field($data['instructor'] ?? ''),
            'program' => sanitize_text_field($data['program'] ?? ''),
            'category' => in_array($data['category'] ?? '', ['A', 'B']) ? $data['category'] : null,
            'package_id' => isset($data['package_id']) ? (int)$data['package_id'] : null,
            'progress' => (float)($data['progress'] ?? 0),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
        ];
    }
}
