<?php

declare(strict_types=1);

namespace Bookando\Modules\tools;

use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Api\Response;
use Bookando\Modules\tools\Services\DesignTemplateService;
use Bookando\Modules\tools\Services\DesignPresetService;
use Bookando\Modules\tools\Services\AccessibilityService;
use Bookando\Modules\tools\Services\CoursePlannerService;
use Bookando\Modules\tools\Services\TimeTrackingService;
use Bookando\Modules\tools\Services\DutySchedulerService;
use Bookando\Modules\tools\Services\WorkforceTimeTrackingService;
use Bookando\Modules\tools\Services\VacationRequestService;
use Bookando\Modules\tools\Services\BookingFormService;
use Bookando\Modules\tools\Services\NotificationService;
use Throwable;
use function __;

class RestHandler
{
    public static function tools($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'message' => __('Tools module loaded', 'bookando'),
            'data'    => [],
        ]);
    }

    public static function reports($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'message' => __('Reports endpoint', 'bookando'),
            'data'    => [
                'reports' => [],
            ],
        ]);
    }

    public static function systemInfo($params, WP_REST_Request $request): WP_REST_Response
    {
        global $wp_version;

        return Response::ok([
            'success' => true,
            'data'    => [
                'wordpress_version' => $wp_version,
                'php_version'       => PHP_VERSION,
                'bookando_version'  => defined('BOOKANDO_VERSION') ? BOOKANDO_VERSION : '1.0.0',
            ],
        ]);
    }

    public static function getCoursePlannerState($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'data'    => CoursePlannerService::getState(),
        ]);
    }

    public static function importCourseHistory($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            CoursePlannerService::importHistory($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Course data imported', 'bookando'),
            'data'    => CoursePlannerService::getState(),
        ]);
    }

    public static function saveCoursePlannerPreferences($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            CoursePlannerService::savePreferences($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Preferences saved', 'bookando'),
            'data'    => CoursePlannerService::getState(),
        ]);
    }

    public static function generateCoursePlan($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $plan = CoursePlannerService::generatePlan($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Plan generated', 'bookando'),
            'data'    => $plan,
        ]);
    }

    public static function getCoursePlannerOffers($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'data'    => CoursePlannerService::getOffersCatalog(),
        ]);
    }

    public static function getTimeTrackingState($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'data'    => TimeTrackingService::getState(),
        ]);
    }

    public static function clockInTimeTracking($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $entry = TimeTrackingService::clockIn($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Timer gestartet', 'bookando'),
            'data'    => [
                'entry' => $entry,
                'state' => TimeTrackingService::getState(),
            ],
        ]);
    }

    public static function clockOutTimeTracking($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $entry = TimeTrackingService::clockOut($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Timer gestoppt', 'bookando'),
            'data'    => [
                'entry' => $entry,
                'state' => TimeTrackingService::getState(),
            ],
        ]);
    }

    public static function createTimeTrackingEntry($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $entry = TimeTrackingService::createEntry($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Zeiterfassung gespeichert', 'bookando'),
            'data'    => [
                'entry' => $entry,
                'state' => TimeTrackingService::getState(),
            ],
        ]);
    }

    public static function saveTimeTrackingRules($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            TimeTrackingService::updateRules($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Zeiterfassungsregeln aktualisiert', 'bookando'),
            'data'    => TimeTrackingService::getState(),
        ]);
    }

    public static function getDutySchedulerState($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'data'    => DutySchedulerService::getState(),
        ]);
    }

    public static function saveDutyTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $state = DutySchedulerService::saveTemplate($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Vorlage gespeichert', 'bookando'),
            'data'    => $state,
        ]);
    }

    public static function saveDutyAvailability($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $state = DutySchedulerService::saveAvailability($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Verfügbarkeit gespeichert', 'bookando'),
            'data'    => $state,
        ]);
    }

    public static function saveDutyConstraints($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $state = DutySchedulerService::updateConstraints($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Dienstregeln aktualisiert', 'bookando'),
            'data'    => $state,
        ]);
    }

    public static function generateDutyRoster($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $state = DutySchedulerService::generateRoster($request->get_json_params() ?? []);
        } catch (Throwable $exception) {
            return Response::error($exception->getMessage(), 400);
        }

        return Response::ok([
            'success' => true,
            'message' => __('Dienstplan erstellt', 'bookando'),
            'data'    => $state,
        ]);
    }

    /**
     * Get all design templates
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function getDesignTemplates($params, WP_REST_Request $request): WP_REST_Response
    {
        $type = $request->get_param('type') ?? null;
        $templates = DesignTemplateService::getAll($type);

        return Response::ok([
            'success' => true,
            'data' => [
                'templates' => $templates,
            ],
        ]);
    }

    /**
     * Get a single design template
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function getDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $template = DesignTemplateService::get($id);

        if (!$template) {
            return Response::error(
                __('Template not found', 'bookando'),
                404
            );
        }

        return Response::ok([
            'success' => true,
            'data' => [
                'template' => $template,
            ],
        ]);
    }

    /**
     * Create a new design template
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function createDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['name'])) {
            return Response::error(
                __('Template name is required', 'bookando'),
                400
            );
        }

        if (empty($data['type'])) {
            return Response::error(
                __('Template type is required', 'bookando'),
                400
            );
        }

        $validTypes = ['service_catalog', 'course_list', 'event_list', 'customer_panel', 'employee_panel'];
        if (!in_array($data['type'], $validTypes)) {
            return Response::error(
                __('Invalid template type', 'bookando'),
                400
            );
        }

        $template = DesignTemplateService::create($data);

        if (!$template) {
            return Response::error(
                __('Failed to create template', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Template created successfully', 'bookando'),
            'data' => [
                'template' => $template,
            ],
        ]);
    }

    /**
     * Update an existing design template
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function updateDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $data = $request->get_json_params();

        $existing = DesignTemplateService::get($id);
        if (!$existing) {
            return Response::error(
                __('Template not found', 'bookando'),
                404
            );
        }

        $template = DesignTemplateService::update($id, $data);

        if (!$template) {
            return Response::error(
                __('Failed to update template', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Template updated successfully', 'bookando'),
            'data' => [
                'template' => $template,
            ],
        ]);
    }

    /**
     * Delete a design template
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function deleteDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];

        $existing = DesignTemplateService::get($id);
        if (!$existing) {
            return Response::error(
                __('Template not found', 'bookando'),
                404
            );
        }

        $success = DesignTemplateService::delete($id);

        if (!$success) {
            return Response::error(
                __('Failed to delete template', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Template deleted successfully', 'bookando'),
        ]);
    }

    /**
     * Compile a design template to CSS
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function compileDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];

        $existing = DesignTemplateService::get($id);
        if (!$existing) {
            return Response::error(
                __('Template not found', 'bookando'),
                404
            );
        }

        $css = DesignTemplateService::compile($id);

        if ($css === null) {
            return Response::error(
                __('Failed to compile template', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Template compiled successfully', 'bookando'),
            'data' => [
                'css' => $css,
                'url' => DesignTemplateService::getCompiledCSSUrl($id),
            ],
        ]);
    }

    /**
     * Get all design presets
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function getDesignPresets($params, WP_REST_Request $request): WP_REST_Response
    {
        $presets = DesignPresetService::getAll();

        return Response::ok([
            'success' => true,
            'data' => [
                'presets' => $presets,
            ],
        ]);
    }

    /**
     * Apply a preset to create a new template
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function applyDesignPreset($params, WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['presetKey'])) {
            return Response::error(
                __('Preset key is required', 'bookando'),
                400
            );
        }

        if (empty($data['name'])) {
            return Response::error(
                __('Template name is required', 'bookando'),
                400
            );
        }

        if (empty($data['type'])) {
            return Response::error(
                __('Template type is required', 'bookando'),
                400
            );
        }

        $template = DesignPresetService::apply(
            $data['presetKey'],
            $data['name'],
            $data['type']
        );

        if (!$template) {
            return Response::error(
                __('Failed to apply preset', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Preset applied successfully', 'bookando'),
            'data' => [
                'template' => $template,
            ],
        ]);
    }

    /**
     * Export a design template as JSON
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function exportDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];

        $template = DesignTemplateService::get($id);
        if (!$template) {
            return Response::error(
                __('Template not found', 'bookando'),
                404
            );
        }

        // Remove database-specific fields
        unset($template['id']);
        unset($template['createdAt']);
        unset($template['updatedAt']);

        // Return as JSON download
        $response = new WP_REST_Response($template);
        $response->header('Content-Type', 'application/json');
        $response->header('Content-Disposition', 'attachment; filename="bookando-template-' . sanitize_file_name($template['name']) . '.json"');

        return $response;
    }

    /**
     * Import a design template from JSON
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function importDesignTemplate($params, WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data)) {
            return Response::error(
                __('Invalid JSON data', 'bookando'),
                400
            );
        }

        if (empty($data['name'])) {
            return Response::error(
                __('Template name is required', 'bookando'),
                400
            );
        }

        if (empty($data['type'])) {
            return Response::error(
                __('Template type is required', 'bookando'),
                400
            );
        }

        // Create new template from imported data
        $template = DesignTemplateService::create($data);

        if (!$template) {
            return Response::error(
                __('Failed to import template', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Template imported successfully', 'bookando'),
            'data' => [
                'template' => $template,
            ],
        ]);
    }

    /**
     * Check color contrast for accessibility (WCAG)
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function checkContrast($params, WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['foreground'])) {
            return Response::error(
                __('Foreground color is required', 'bookando'),
                400
            );
        }

        if (empty($data['background'])) {
            return Response::error(
                __('Background color is required', 'bookando'),
                400
            );
        }

        $result = AccessibilityService::checkContrast(
            $data['foreground'],
            $data['background']
        );

        if (isset($result['error'])) {
            return Response::error(
                $result['error'],
                400
            );
        }

        return Response::ok([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Validate a design template for accessibility
     *
     * @param array $params Route parameters
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function validateTemplateAccessibility($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];

        $template = DesignTemplateService::get($id);
        if (!$template) {
            return Response::error(
                __('Template not found', 'bookando'),
                404
            );
        }

        $result = AccessibilityService::validateTemplate($template);

        return Response::ok([
            'success' => true,
            'data' => $result,
        ]);
    }

    // =====================================================
    // Workforce Management Handlers (NEW)
    // =====================================================

    /**
     * Get workforce time tracking state with employees and recent entries.
     */
    public static function getWorkforceTimeState($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userId = $request->get_param('user_id') ? (int) $request->get_param('user_id') : null;
            $limit = $request->get_param('limit') ? (int) $request->get_param('limit') : 50;

            $state = WorkforceTimeTrackingService::getState($userId, $limit);

            return Response::ok([
                'success' => true,
                'data' => $state,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get list of active employees for selection.
     */
    public static function getActiveEmployees($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $status = $request->get_param('status') ?? 'active';
            $employees = WorkforceTimeTrackingService::getActiveEmployees($status);

            return Response::ok([
                'success' => true,
                'data' => ['employees' => $employees],
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Clock in (start timer) for an employee.
     */
    public static function workforceClockIn($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $result = WorkforceTimeTrackingService::clockIn($userId, $body);

            return Response::ok([
                'success' => true,
                'message' => __('Timer started successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Clock out (stop timer) for an employee.
     */
    public static function workforceClockOut($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $result = WorkforceTimeTrackingService::clockOut($userId, $body);

            return Response::ok([
                'success' => true,
                'message' => __('Timer stopped successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Create manual time entry.
     */
    public static function createWorkforceEntry($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $result = WorkforceTimeTrackingService::createManualEntry($userId, $body);

            return Response::ok([
                'success' => true,
                'message' => __('Time entry created successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get vacation requests with optional filters.
     */
    public static function getVacationRequests($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $filters = [
                'user_id' => $request->get_param('user_id') ? (int) $request->get_param('user_id') : null,
                'status' => $request->get_param('status'),
                'year' => $request->get_param('year') ? (int) $request->get_param('year') : null,
            ];

            $requests = VacationRequestService::getRequests(array_filter($filters));

            return Response::ok([
                'success' => true,
                'data' => ['requests' => $requests],
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create a vacation request.
     */
    public static function createVacationRequest($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $result = VacationRequestService::createRequest($userId, $body);

            return Response::ok([
                'success' => true,
                'message' => __('Vacation request created successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Approve a vacation request.
     */
    public static function approveVacationRequest($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $requestId = isset($params['id']) ? (int) $params['id'] : 0;
            $body = $request->get_json_params() ?? [];
            $reviewerId = isset($body['reviewer_id']) ? (int) $body['reviewer_id'] : get_current_user_id();

            if (!$requestId) {
                return Response::error(__('Request ID is required', 'bookando'), 400);
            }

            $result = VacationRequestService::approveRequest($requestId, $reviewerId);

            return Response::ok([
                'success' => true,
                'message' => __('Vacation request approved', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Reject a vacation request.
     */
    public static function rejectVacationRequest($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $requestId = isset($params['id']) ? (int) $params['id'] : 0;
            $body = $request->get_json_params() ?? [];
            $reviewerId = isset($body['reviewer_id']) ? (int) $body['reviewer_id'] : get_current_user_id();
            $reason = $body['reason'] ?? null;

            if (!$requestId) {
                return Response::error(__('Request ID is required', 'bookando'), 400);
            }

            $result = VacationRequestService::rejectRequest($requestId, $reviewerId, $reason);

            return Response::ok([
                'success' => true,
                'message' => __('Vacation request rejected', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Cancel a vacation request.
     */
    public static function cancelVacationRequest($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $requestId = isset($params['id']) ? (int) $params['id'] : 0;
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : get_current_user_id();

            if (!$requestId) {
                return Response::error(__('Request ID is required', 'bookando'), 400);
            }

            $result = VacationRequestService::cancelRequest($requestId, $userId);

            return Response::ok([
                'success' => true,
                'message' => __('Vacation request cancelled', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get vacation overview for an employee.
     */
    public static function getVacationOverview($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userId = isset($params['user_id']) ? (int) $params['user_id'] : 0;
            $year = $request->get_param('year') ? (int) $request->get_param('year') : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $overview = VacationRequestService::getEmployeeOverview($userId, $year);

            return Response::ok([
                'success' => true,
                'data' => $overview,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get workforce calendar data (combining workdays, special days, vacations, bookings).
     */
    public static function getWorkforceCalendar($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userId = $request->get_param('user_id') ? (int) $request->get_param('user_id') : null;
            $month = $request->get_param('month');
            $year = $request->get_param('year') ? (int) $request->get_param('year') : null;

            $calendarData = WorkforceTimeTrackingService::getCalendarData($userId, $month, $year);

            return Response::ok([
                'success' => true,
                'data' => $calendarData,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all booking forms
     */
    public static function getBookingForms($params, WP_REST_Request $request): WP_REST_Response
    {
        $forms = BookingFormService::getAll();

        return Response::ok([
            'success' => true,
            'data' => [
                'forms' => $forms,
            ],
        ]);
    }

    /**
     * Get a single booking form
     */
    public static function getBookingForm($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $form = BookingFormService::get($id);

        if (!$form) {
            return Response::error(
                __('Booking form not found', 'bookando'),
                404
            );
        }

        return Response::ok([
            'success' => true,
            'data' => [
                'form' => $form,
            ],
        ]);
    }

    /**
     * Create a new booking form
     */
    public static function createBookingForm($params, WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['name'])) {
            return Response::error(
                __('Form name is required', 'bookando'),
                400
            );
        }

        $form = BookingFormService::create($data);

        if (!$form) {
            return Response::error(
                __('Failed to create booking form', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Booking form created successfully', 'bookando'),
            'data' => [
                'form' => $form,
            ],
        ]);
    }

    /**
     * Update an existing booking form
     */
    public static function updateBookingForm($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $data = $request->get_json_params();

        $existing = BookingFormService::get($id);
        if (!$existing) {
            return Response::error(
                __('Booking form not found', 'bookando'),
                404
            );
        }

        $form = BookingFormService::update($id, $data);

        if (!$form) {
            return Response::error(
                __('Failed to update booking form', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Booking form updated successfully', 'bookando'),
            'data' => [
                'form' => $form,
            ],
        ]);
    }

    /**
     * Delete a booking form
     */
    public static function deleteBookingForm($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];

        $existing = BookingFormService::get($id);
        if (!$existing) {
            return Response::error(
                __('Booking form not found', 'bookando'),
                404
            );
        }

        if ($existing['is_default']) {
            return Response::error(
                __('Cannot delete default booking form', 'bookando'),
                400
            );
        }

        $success = BookingFormService::delete($id);

        if (!$success) {
            return Response::error(
                __('Failed to delete booking form', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Booking form deleted successfully', 'bookando'),
        ]);
    }

    /**
     * Get all notification matrices
     */
    public static function getNotifications($params, WP_REST_Request $request): WP_REST_Response
    {
        $notifications = NotificationService::getAll();

        return Response::ok([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
            ],
        ]);
    }

    /**
     * Get a single notification matrix
     */
    public static function getNotification($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $notification = NotificationService::get($id);

        if (!$notification) {
            return Response::error(
                __('Notification not found', 'bookando'),
                404
            );
        }

        return Response::ok([
            'success' => true,
            'data' => [
                'notification' => $notification,
            ],
        ]);
    }

    /**
     * Create a new notification matrix
     */
    public static function createNotification($params, WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['name'])) {
            return Response::error(
                __('Notification name is required', 'bookando'),
                400
            );
        }

        $notification = NotificationService::create($data);

        if (!$notification) {
            return Response::error(
                __('Failed to create notification', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Notification created successfully', 'bookando'),
            'data' => [
                'notification' => $notification,
            ],
        ]);
    }

    /**
     * Update an existing notification matrix
     */
    public static function updateNotification($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $data = $request->get_json_params();

        $existing = NotificationService::get($id);
        if (!$existing) {
            return Response::error(
                __('Notification not found', 'bookando'),
                404
            );
        }

        $notification = NotificationService::update($id, $data);

        if (!$notification) {
            return Response::error(
                __('Failed to update notification', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Notification updated successfully', 'bookando'),
            'data' => [
                'notification' => $notification,
            ],
        ]);
    }

    /**
     * Delete a notification matrix
     */
    public static function deleteNotification($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];

        $existing = NotificationService::get($id);
        if (!$existing) {
            return Response::error(
                __('Notification not found', 'bookando'),
                404
            );
        }

        $success = NotificationService::delete($id);

        if (!$success) {
            return Response::error(
                __('Failed to delete notification', 'bookando'),
                500
            );
        }

        return Response::ok([
            'success' => true,
            'message' => __('Notification deleted successfully', 'bookando'),
        ]);
    }

    /**
     * Get notification logs
     */
    public static function getNotificationLogs($params, WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'channel' => $request->get_param('channel'),
            'status' => $request->get_param('status'),
            'date' => $request->get_param('date'),
        ];

        $logs = NotificationService::getLogs($filters);

        return Response::ok([
            'success' => true,
            'data' => [
                'logs' => $logs,
            ],
        ]);
    }

    /**
     * Get a single notification log
     */
    public static function getNotificationLog($params, WP_REST_Request $request): WP_REST_Response
    {
        $id = (int)$params['id'];
        $log = NotificationService::getLog($id);

        if (!$log) {
            return Response::error(
                __('Log not found', 'bookando'),
                404
            );
        }

        return Response::ok([
            'success' => true,
            'data' => [
                'log' => $log,
            ],
        ]);
    }
}
