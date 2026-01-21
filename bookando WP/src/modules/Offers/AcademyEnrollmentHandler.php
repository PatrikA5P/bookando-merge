<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

use Bookando\Core\Licensing\LicenseManager;

/**
 * Academy Enrollment Handler
 *
 * Handles automatic enrollment of users into Academy courses when they purchase/complete an offer.
 * Requires Academy module license.
 */
final class AcademyEnrollmentHandler
{
    /**
     * Enroll user in Academy courses based on offer configuration
     *
     * @param int $userId User ID from bookando_users
     * @param int $offerId Offer ID
     * @param string $trigger When to enroll: 'immediate' (on purchase), 'on_completion' (after completion)
     * @return array{success: bool, enrolled: int[], errors: array}
     */
    public static function enrollUserInAcademyCourses(int $userId, int $offerId, string $trigger = 'immediate'): array
    {
        // Check Academy module license
        if (!self::hasAcademyLicense()) {
            return [
                'success' => false,
                'enrolled' => [],
                'errors' => ['Academy module license not found or inactive'],
            ];
        }

        $model = new Model();
        $offer = $model->find($offerId);

        if (!$offer) {
            return [
                'success' => false,
                'enrolled' => [],
                'errors' => ['Offer not found'],
            ];
        }

        // Check if auto-enrollment is enabled
        if (empty($offer['auto_enroll_academy'])) {
            return [
                'success' => false,
                'enrolled' => [],
                'errors' => ['Auto-enrollment not enabled for this offer'],
            ];
        }

        // Check if access type matches trigger
        $accessType = $offer['academy_access_type'] ?? 'on_completion';
        if ($accessType !== $trigger) {
            return [
                'success' => false,
                'enrolled' => [],
                'errors' => ["Access type is '{$accessType}', but trigger is '{$trigger}'"],
            ];
        }

        // Get Academy course IDs
        $courseIds = $offer['academy_course_ids'] ?? [];
        if (empty($courseIds)) {
            return [
                'success' => false,
                'enrolled' => [],
                'errors' => ['No Academy courses configured for this offer'],
            ];
        }

        // Calculate expiration date
        $expiresAt = null;
        if (!empty($offer['academy_access_duration_days'])) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)$offer['academy_access_duration_days'] . ' days'));
        }

        // Enroll in each course
        $enrolled = [];
        $errors = [];

        foreach ($courseIds as $courseId) {
            $result = self::enrollInCourse(
                $userId,
                (int)$courseId,
                $offerId,
                $expiresAt,
                (bool)($offer['academy_certificate_on_completion'] ?? true)
            );

            if ($result['success']) {
                $enrolled[] = (int)$courseId;
            } else {
                $errors[] = "Course {$courseId}: " . $result['error'];
            }
        }

        return [
            'success' => count($enrolled) > 0,
            'enrolled' => $enrolled,
            'errors' => $errors,
        ];
    }

    /**
     * Enroll user in a single Academy course
     *
     * @param int $userId User ID
     * @param int $courseId Academy course ID
     * @param int $offerId Offer ID (for reference)
     * @param string|null $expiresAt Expiration date (NULL = lifetime access)
     * @param bool $certificateEnabled Award certificate on completion
     * @return array{success: bool, error?: string}
     */
    protected static function enrollInCourse(
        int $userId,
        int $courseId,
        int $offerId,
        ?string $expiresAt,
        bool $certificateEnabled
    ): array {
        global $wpdb;

        // Check if Academy enrollments table exists
        $enrollmentTable = $wpdb->prefix . 'bookando_academy_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enrollmentTable}'") !== $enrollmentTable) {
            return [
                'success' => false,
                'error' => 'Academy enrollments table not found',
            ];
        }

        // Check if already enrolled
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$enrollmentTable}
             WHERE user_id = %d AND course_id = %d AND deleted_at IS NULL",
            $userId,
            $courseId
        ));

        if ($existing) {
            // Update existing enrollment
            $updated = $wpdb->update(
                $enrollmentTable,
                [
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                    'source_type' => 'offer',
                    'source_id' => $offerId,
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $existing],
                ['%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );

            return [
                'success' => $updated !== false,
                'error' => $updated === false ? 'Failed to update enrollment' : null,
            ];
        }

        // Create new enrollment
        $inserted = $wpdb->insert(
            $enrollmentTable,
            [
                'user_id' => $userId,
                'course_id' => $courseId,
                'status' => 'active',
                'progress' => 0,
                'expires_at' => $expiresAt,
                'certificate_enabled' => $certificateEnabled ? 1 : 0,
                'source_type' => 'offer',
                'source_id' => $offerId,
                'enrolled_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s']
        );

        return [
            'success' => $inserted !== false,
            'error' => $inserted === false ? 'Failed to create enrollment' : null,
        ];
    }

    /**
     * Revoke Academy course access for a user
     *
     * @param int $userId User ID
     * @param int $offerId Offer ID
     * @return array{success: bool, revoked: int}
     */
    public static function revokeCourseAccess(int $userId, int $offerId): array
    {
        global $wpdb;

        $enrollmentTable = $wpdb->prefix . 'bookando_academy_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enrollmentTable}'") !== $enrollmentTable) {
            return ['success' => false, 'revoked' => 0];
        }

        $result = $wpdb->update(
            $enrollmentTable,
            [
                'status' => 'revoked',
                'updated_at' => current_time('mysql'),
            ],
            [
                'user_id' => $userId,
                'source_type' => 'offer',
                'source_id' => $offerId,
            ],
            ['%s', '%s'],
            ['%d', '%s', '%d']
        );

        return [
            'success' => $result !== false,
            'revoked' => $result !== false ? $result : 0,
        ];
    }

    /**
     * Extend course access duration
     *
     * @param int $userId User ID
     * @param int $offerId Offer ID
     * @param int $additionalDays Additional days to add
     * @return array{success: bool, extended: int}
     */
    public static function extendCourseAccess(int $userId, int $offerId, int $additionalDays): array
    {
        global $wpdb;

        $enrollmentTable = $wpdb->prefix . 'bookando_academy_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enrollmentTable}'") !== $enrollmentTable) {
            return ['success' => false, 'extended' => 0];
        }

        // Update expiration dates
        $sql = $wpdb->prepare(
            "UPDATE {$enrollmentTable}
             SET expires_at = DATE_ADD(COALESCE(expires_at, NOW()), INTERVAL %d DAY),
                 updated_at = %s
             WHERE user_id = %d
               AND source_type = 'offer'
               AND source_id = %d
               AND status = 'active'
               AND deleted_at IS NULL",
            $additionalDays,
            current_time('mysql'),
            $userId,
            $offerId
        );

        $result = $wpdb->query($sql);

        return [
            'success' => $result !== false,
            'extended' => $result !== false ? $result : 0,
        ];
    }

    /**
     * Get user's Academy enrollments from an offer
     *
     * @param int $userId User ID
     * @param int $offerId Offer ID
     * @return array<int, array>
     */
    public static function getUserEnrollments(int $userId, int $offerId): array
    {
        global $wpdb;

        $enrollmentTable = $wpdb->prefix . 'bookando_academy_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enrollmentTable}'") !== $enrollmentTable) {
            return [];
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$enrollmentTable}
             WHERE user_id = %d
               AND source_type = 'offer'
               AND source_id = %d
               AND deleted_at IS NULL
             ORDER BY enrolled_at DESC",
            $userId,
            $offerId
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Check if Academy module license is active
     */
    public static function hasAcademyLicense(): bool
    {
        $result = LicenseManager::ensureFeature('academy', 'rest_api');
        return !is_wp_error($result);
    }

    /**
     * Check if user has active access to Academy courses from offer
     *
     * @param int $userId User ID
     * @param int $offerId Offer ID
     * @return bool
     */
    public static function hasActiveAccess(int $userId, int $offerId): bool
    {
        global $wpdb;

        $enrollmentTable = $wpdb->prefix . 'bookando_academy_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enrollmentTable}'") !== $enrollmentTable) {
            return false;
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$enrollmentTable}
             WHERE user_id = %d
               AND source_type = 'offer'
               AND source_id = %d
               AND status = 'active'
               AND (expires_at IS NULL OR expires_at > NOW())
               AND deleted_at IS NULL",
            $userId,
            $offerId
        ));

        return (int)$count > 0;
    }

    /**
     * Get offer statistics with Academy enrollment data
     *
     * @param int $offerId Offer ID
     * @return array{total_enrollments: int, active_enrollments: int, completed: int}
     */
    public static function getOfferStats(int $offerId): array
    {
        global $wpdb;

        $enrollmentTable = $wpdb->prefix . 'bookando_academy_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enrollmentTable}'") !== $enrollmentTable) {
            return [
                'total_enrollments' => 0,
                'active_enrollments' => 0,
                'completed' => 0,
            ];
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_enrollments,
                SUM(CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) THEN 1 ELSE 0 END) as active_enrollments,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM {$enrollmentTable}
             WHERE source_type = 'offer'
               AND source_id = %d
               AND deleted_at IS NULL",
            $offerId
        ), ARRAY_A);

        return [
            'total_enrollments' => (int)($stats['total_enrollments'] ?? 0),
            'active_enrollments' => (int)($stats['active_enrollments'] ?? 0),
            'completed' => (int)($stats['completed'] ?? 0),
        ];
    }
}
