<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

use Bookando\Core\Api\Response;
use WP_REST_Request;
use WP_REST_Response;

class RestHandler
{
    /**
     * Get public offers (courses, packages, appointments)
     */
    public static function getOffers(WP_REST_Request $request): WP_REST_Response
    {
        $category = $request->get_param('category');
        $tag = $request->get_param('tag');
        $featured = $request->get_param('featured');
        $limit = (int)($request->get_param('limit') ?: 12);

        // Load offers from database
        $offers = self::loadOffers([
            'category' => $category,
            'tag' => $tag,
            'featured' => $featured,
            'limit' => $limit,
        ]);

        return Response::ok($offers);
    }

    /**
     * Get single offer
     */
    public static function getOffer(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$request->get_param('id');

        $offer = self::loadOfferById($id);

        if (!$offer) {
            return Response::error(['message' => 'Angebot nicht gefunden'], 404);
        }

        return Response::ok($offer);
    }

    /**
     * Auth: Email login
     */
    public static function authEmailLogin(WP_REST_Request $request): WP_REST_Response
    {
        $email = $request->get_param('email');
        $password = $request->get_param('password');

        if (!$email || !$password) {
            return Response::error(['message' => 'Email und Passwort erforderlich'], 400);
        }

        $result = AuthHandler::authenticateEmail($email, $password);

        if (is_wp_error($result)) {
            return Response::error(['message' => $result->get_error_message()], 401);
        }

        return Response::ok($result);
    }

    /**
     * Auth: Email verification
     */
    public static function authEmailVerify(WP_REST_Request $request): WP_REST_Response
    {
        $token = $request->get_param('token');

        if (!$token) {
            return Response::error(['message' => 'Token erforderlich'], 400);
        }

        $result = AuthHandler::verifyEmailToken($token);

        if (is_wp_error($result)) {
            return Response::error(['message' => $result->get_error_message()], 400);
        }

        return Response::ok($result);
    }

    /**
     * Auth: Google login
     */
    public static function authGoogleLogin(WP_REST_Request $request): WP_REST_Response
    {
        $googleToken = $request->get_param('token');

        if (!$googleToken) {
            return Response::error(['message' => 'Google-Token erforderlich'], 400);
        }

        $result = AuthHandler::authenticateGoogle($googleToken);

        if (is_wp_error($result)) {
            return Response::error(['message' => $result->get_error_message()], 401);
        }

        return Response::ok($result);
    }

    /**
     * Auth: Apple login
     */
    public static function authAppleLogin(WP_REST_Request $request): WP_REST_Response
    {
        $appleToken = $request->get_param('token');

        if (!$appleToken) {
            return Response::error(['message' => 'Apple-Token erforderlich'], 400);
        }

        $result = AuthHandler::authenticateApple($appleToken);

        if (is_wp_error($result)) {
            return Response::error(['message' => $result->get_error_message()], 401);
        }

        return Response::ok($result);
    }

    /**
     * Auth: Logout
     */
    public static function authLogout(WP_REST_Request $request): WP_REST_Response
    {
        $token = $request->get_header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        AuthHandler::logout($token);

        return Response::ok(['message' => 'Abgemeldet']);
    }

    /**
     * Get customer bookings
     */
    public static function getCustomerBookings(WP_REST_Request $request): WP_REST_Response
    {
        $userId = self::getUserIdFromRequest($request);

        // Load bookings from Appointments module
        $bookings = self::loadCustomerBookings($userId);

        return Response::ok($bookings);
    }

    /**
     * Get customer invoices
     */
    public static function getCustomerInvoices(WP_REST_Request $request): WP_REST_Response
    {
        $userId = self::getUserIdFromRequest($request);

        // Load invoices from Finance module
        $invoices = self::loadCustomerInvoices($userId);

        return Response::ok($invoices);
    }

    /**
     * Get customer progress (Academy)
     */
    public static function getCustomerProgress(WP_REST_Request $request): WP_REST_Response
    {
        $userId = self::getUserIdFromRequest($request);

        // Load progress from Academy module
        $progress = self::loadCustomerProgress($userId);

        return Response::ok($progress);
    }

    /**
     * Get employee schedule
     */
    public static function getEmployeeSchedule(WP_REST_Request $request): WP_REST_Response
    {
        $userId = self::getUserIdFromRequest($request);

        // Load schedule from Appointments module
        $schedule = self::loadEmployeeSchedule($userId);

        return Response::ok($schedule);
    }

    /**
     * Get employee students
     */
    public static function getEmployeeStudents(WP_REST_Request $request): WP_REST_Response
    {
        $userId = self::getUserIdFromRequest($request);

        // Load students from Academy module
        $students = self::loadEmployeeStudents($userId);

        return Response::ok($students);
    }

    /**
     * Create booking
     */
    public static function createBooking(WP_REST_Request $request): WP_REST_Response
    {
        $userId = self::getUserIdFromRequest($request);
        $offerId = (int)$request->get_param('offer_id');
        $offerType = $request->get_param('offer_type');
        $data = $request->get_json_params();

        // Create booking via Appointments module
        $booking = self::createAppointmentBooking($userId, $offerId, $offerType, $data);

        if (is_wp_error($booking)) {
            return Response::error(['message' => $booking->get_error_message()], 400);
        }

        return Response::created($booking);
    }

    /**
     * Permission callback: Is authenticated
     */
    public static function isAuthenticated(WP_REST_Request $request): bool
    {
        $token = $request->get_header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        return (bool)AuthHandler::validateSession($token);
    }

    /**
     * Permission callback: Is employee
     */
    public static function isEmployee(WP_REST_Request $request): bool
    {
        if (!self::isAuthenticated($request)) {
            return false;
        }

        $userId = self::getUserIdFromRequest($request);
        $user = get_userdata($userId);

        return in_array('bookando_instructor', $user->roles) || in_array('administrator', $user->roles);
    }

    /**
     * Get user ID from request
     */
    protected static function getUserIdFromRequest(WP_REST_Request $request): int
    {
        $token = $request->get_header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        return (int)AuthHandler::validateSession($token);
    }

    /**
     * Load offers from database
     */
    protected static function loadOffers(array $filters): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_offer_displays';

        $where = ['visible = 1'];
        $params = [];

        if (!empty($filters['featured'])) {
            $where[] = 'featured = 1';
        }

        if (!empty($filters['category'])) {
            $where[] = "JSON_CONTAINS(categories, %s)";
            $params[] = '"' . $filters['category'] . '"';
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY display_order ASC, created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [];
    }

    /**
     * Load single offer by ID
     */
    protected static function loadOfferById(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_offer_displays';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND visible = 1",
            $id
        ), ARRAY_A);
    }

    /**
     * Load customer bookings
     */
    protected static function loadCustomerBookings(int $userId): array
    {
        // Integration with Appointments module
        // Placeholder - to be implemented
        return [];
    }

    /**
     * Load customer invoices
     */
    protected static function loadCustomerInvoices(int $userId): array
    {
        // Integration with Finance module
        // Placeholder - to be implemented
        return [];
    }

    /**
     * Load customer progress
     */
    protected static function loadCustomerProgress(int $userId): array
    {
        // Integration with Academy module
        // Placeholder - to be implemented
        return [];
    }

    /**
     * Load employee schedule
     */
    protected static function loadEmployeeSchedule(int $userId): array
    {
        // Integration with Appointments module
        // Placeholder - to be implemented
        return [];
    }

    /**
     * Load employee students
     */
    protected static function loadEmployeeStudents(int $userId): array
    {
        // Integration with Academy module
        // Placeholder - to be implemented
        return [];
    }

    /**
     * Create appointment booking
     */
    protected static function createAppointmentBooking(int $userId, int $offerId, string $offerType, array $data)
    {
        // Integration with Appointments module
        // Placeholder - to be implemented
        return new \WP_Error('not_implemented', 'Booking creation not yet implemented');
    }
}
