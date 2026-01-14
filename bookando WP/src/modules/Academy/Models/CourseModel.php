<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy\Models;

class CourseModel
{
    protected \wpdb $db;
    protected string $table;
    protected string $topicsTable;
    protected string $lessonsTable;
    protected string $quizzesTable;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'bookando_academy_courses';
        $this->topicsTable = $wpdb->prefix . 'bookando_academy_topics';
        $this->lessonsTable = $wpdb->prefix . 'bookando_academy_lessons';
        $this->quizzesTable = $wpdb->prefix . 'bookando_academy_quizzes';
    }

    /**
     * Alle Kurse laden (mit Topics, Lessons, Quizzes).
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY created_at DESC";
        $courses = $this->db->get_results($sql, ARRAY_A) ?: [];

        foreach ($courses as &$course) {
            $course['topics'] = $this->getTopicsForCourse((int)$course['id']);
        }

        return $courses;
    }

    /**
     * Kurs per ID laden (mit Topics, Lessons, Quizzes).
     */
    public function find(int $id): ?array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        $course = $this->db->get_row($sql, ARRAY_A);

        if (!$course) {
            return null;
        }

        $course['topics'] = $this->getTopicsForCourse($id);
        return $course;
    }

    /**
     * Kurs erstellen oder aktualisieren.
     */
    public function save(array $data): int
    {
        // Topics und Lessons extrahieren
        $topics = $data['topics'] ?? [];
        unset($data['topics']);

        error_log('[Bookando Academy] Saving course with ' . count($topics) . ' topics');

        // Sanitize course data
        $courseData = $this->sanitizeCourse($data);

        if (!empty($courseData['id'])) {
            // Update
            $id = (int)$courseData['id'];
            unset($courseData['id']);
            $courseData['updated_at'] = current_time('mysql');

            $this->db->update($this->table, $courseData, ['id' => $id]);
            error_log('[Bookando Academy] Updated course ID: ' . $id);

            // Lösche alte Topics und erstelle neue
            $this->db->delete($this->topicsTable, ['course_id' => $id]);
        } else {
            // Insert
            unset($courseData['id']);
            $courseData['created_at'] = current_time('mysql');
            $courseData['updated_at'] = $courseData['created_at'];

            $this->db->insert($this->table, $courseData);
            $id = (int)$this->db->insert_id;
            error_log('[Bookando Academy] Created course ID: ' . $id);
        }

        // Topics speichern
        $topicCount = 0;
        foreach ($topics as $orderIndex => $topic) {
            $topicId = $this->saveTopic($id, $topic, $orderIndex);
            $topicCount++;
            error_log('[Bookando Academy] Saved topic ID: ' . $topicId . ' for course ' . $id);
        }
        error_log('[Bookando Academy] Total topics saved: ' . $topicCount);

        return $id;
    }

    /**
     * Kurs löschen (mit allen Topics, Lessons, Quizzes via CASCADE).
     */
    public function delete(int $id): bool
    {
        error_log('[Bookando Academy CourseModel] Attempting to delete course ID: ' . $id);

        // Prüfe ob Kurs existiert
        $exists = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE id = %d",
            $id
        ));

        if (!$exists) {
            error_log('[Bookando Academy CourseModel] Course ID ' . $id . ' does not exist');
            return false;
        }

        $result = (bool)$this->db->delete($this->table, ['id' => $id]);

        if ($this->db->last_error) {
            error_log('[Bookando Academy CourseModel] Delete error: ' . $this->db->last_error);
        }

        error_log('[Bookando Academy CourseModel] Delete result for ID ' . $id . ': ' . ($result ? 'success' : 'failed'));
        return $result;
    }

    /**
     * Topics für einen Kurs laden.
     */
    protected function getTopicsForCourse(int $courseId): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->topicsTable} WHERE course_id = %d ORDER BY order_index ASC",
            $courseId
        );
        $topics = $this->db->get_results($sql, ARRAY_A) ?: [];

        foreach ($topics as &$topic) {
            $topicId = (int)$topic['id'];
            $topic['lessons'] = $this->getLessonsForTopic($topicId);
            $topic['quizzes'] = $this->getQuizzesForTopic($topicId);
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
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Quizzes für ein Topic laden.
     */
    protected function getQuizzesForTopic(int $topicId): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->quizzesTable} WHERE topic_id = %d ORDER BY order_index ASC",
            $topicId
        );
        $quizzes = $this->db->get_results($sql, ARRAY_A) ?: [];

        // JSON decode questions
        foreach ($quizzes as &$quiz) {
            if (!empty($quiz['questions'])) {
                $quiz['questions'] = json_decode($quiz['questions'], true);
            }
        }

        return $quizzes;
    }

    /**
     * Topic speichern (mit Lessons und Quizzes).
     */
    protected function saveTopic(int $courseId, array $topicData, int $orderIndex): int
    {
        $lessons = $topicData['lessons'] ?? [];
        $quizzes = $topicData['quizzes'] ?? [];
        unset($topicData['lessons'], $topicData['quizzes']);

        $topicData['course_id'] = $courseId;
        $topicData['order_index'] = $orderIndex;
        $topicData['title'] = sanitize_text_field($topicData['title'] ?? '');
        $topicData['description'] = sanitize_textarea_field($topicData['description'] ?? '');
        $topicData['created_at'] = current_time('mysql');
        $topicData['updated_at'] = $topicData['created_at'];

        unset($topicData['id']); // Ignore alte IDs
        $this->db->insert($this->topicsTable, $topicData);
        $topicId = (int)$this->db->insert_id;

        // Lessons speichern
        foreach ($lessons as $lessonIndex => $lesson) {
            $this->saveLesson($topicId, $lesson, $lessonIndex);
        }

        // Quizzes speichern
        foreach ($quizzes as $quizIndex => $quiz) {
            $this->saveQuiz($topicId, $quiz, $quizIndex);
        }

        return $topicId;
    }

    /**
     * Lesson speichern.
     */
    protected function saveLesson(int $topicId, array $lessonData, int $orderIndex): int
    {
        $lessonData['topic_id'] = $topicId;
        $lessonData['order_index'] = $orderIndex;
        $lessonData['title'] = sanitize_text_field($lessonData['title'] ?? '');
        $lessonData['content'] = wp_kses_post($lessonData['content'] ?? '');
        $lessonData['duration'] = (int)($lessonData['duration'] ?? 0);
        $lessonData['created_at'] = current_time('mysql');
        $lessonData['updated_at'] = $lessonData['created_at'];

        unset($lessonData['id']); // Ignore alte IDs
        $this->db->insert($this->lessonsTable, $lessonData);
        return (int)$this->db->insert_id;
    }

    /**
     * Quiz speichern.
     */
    protected function saveQuiz(int $topicId, array $quizData, int $orderIndex): int
    {
        $quizData['topic_id'] = $topicId;
        $quizData['order_index'] = $orderIndex;
        $quizData['title'] = sanitize_text_field($quizData['title'] ?? '');
        $quizData['questions'] = !empty($quizData['questions']) ? wp_json_encode($quizData['questions']) : '[]';
        $quizData['created_at'] = current_time('mysql');
        $quizData['updated_at'] = $quizData['created_at'];

        unset($quizData['id']); // Ignore alte IDs
        $this->db->insert($this->quizzesTable, $quizData);
        return (int)$this->db->insert_id;
    }

    /**
     * Course-Daten bereinigen.
     */
    protected function sanitizeCourse(array $data): array
    {
        return [
            'id' => !empty($data['id']) ? (int)$data['id'] : null,
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'course_type' => sanitize_text_field($data['course_type'] ?? 'online'),
            'category' => sanitize_text_field($data['category'] ?? ''),
            'level' => sanitize_text_field($data['level'] ?? 'beginner'),
            'visibility' => sanitize_text_field($data['visibility'] ?? 'public'),
            'featured_image' => !empty($data['featured_image']) ? esc_url_raw($data['featured_image']) : '',
            'author' => sanitize_text_field($data['author'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
        ];
    }
}
