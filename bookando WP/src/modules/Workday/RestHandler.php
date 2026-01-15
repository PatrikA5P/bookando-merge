<?php

declare(strict_types=1);

namespace Bookando\Modules\Workday;

use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Api\Response;
use Bookando\Modules\Workday\Services\WorkforceTimeTrackingService;
use Bookando\Modules\Workday\Services\VacationRequestService;
use Bookando\Modules\Workday\Services\DutySchedulerService;
use Throwable;
use function __;

class RestHandler
{
    // =====================================================
    // Time Tracking Handlers
    // =====================================================

    /**
     * Get workforce time tracking state with employees and recent entries.
     */
    public static function getTimeTrackingState($params, WP_REST_Request $request): WP_REST_Response
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
    public static function clockIn($params, WP_REST_Request $request): WP_REST_Response
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
    public static function clockOut($params, WP_REST_Request $request): WP_REST_Response
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
    public static function createManualEntry($params, WP_REST_Request $request): WP_REST_Response
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

    // =====================================================
    // Vacation Request Handlers
    // =====================================================

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

    // =====================================================
    // Calendar Handlers
    // =====================================================

    /**
     * Get workforce calendar data (combining workdays, special days, vacations, bookings).
     */
    public static function getCalendar($params, WP_REST_Request $request): WP_REST_Response
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

    // =====================================================
    // Duty Scheduling Handlers
    // =====================================================

    /**
     * Get duty scheduler state.
     */
    public static function getDutySchedulerState($params, WP_REST_Request $request): WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'data' => DutySchedulerService::getState(),
        ]);
    }

    /**
     * Save duty template.
     */
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
            'data' => $state,
        ]);
    }

    /**
     * Save duty availability.
     */
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
            'data' => $state,
        ]);
    }

    /**
     * Save duty constraints.
     */
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
            'data' => $state,
        ]);
    }

    /**
     * Generate duty roster.
     */
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
            'data' => $state,
        ]);
    }

    // =====================================================
    // Appointment Handlers (PLACEHOLDER - To be implemented)
    // =====================================================

    /**
     * Get all appointments.
     */
    public static function getAppointments($params, WP_REST_Request $request): WP_REST_Response
    {
        // TODO: Implement appointment retrieval
        return Response::ok([
            'success' => true,
            'data' => [
                'appointments' => [],
            ],
        ]);
    }

    /**
     * Create a new appointment.
     */
    public static function createAppointment($params, WP_REST_Request $request): WP_REST_Response
    {
        // TODO: Implement appointment creation
        return Response::ok([
            'success' => true,
            'message' => __('Appointment created successfully', 'bookando'),
            'data' => [],
        ]);
    }

    /**
     * Update an existing appointment.
     */
    public static function updateAppointment($params, WP_REST_Request $request): WP_REST_Response
    {
        // TODO: Implement appointment update
        return Response::ok([
            'success' => true,
            'message' => __('Appointment updated successfully', 'bookando'),
            'data' => [],
        ]);
    }

    /**
     * Delete an appointment.
     */
    public static function deleteAppointment($params, WP_REST_Request $request): WP_REST_Response
    {
        // TODO: Implement appointment deletion
        return Response::ok([
            'success' => true,
            'message' => __('Appointment deleted successfully', 'bookando'),
        ]);
    }

    // =====================================================
    // Break Tracking Handlers
    // =====================================================

    /**
     * Start a break for an active timer.
     */
    public static function startBreak($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $result = \Bookando\Modules\Workday\Services\BreakService::startBreak($userId, $body);

            return Response::ok([
                'success' => true,
                'message' => __('Break started successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * End a break for an active timer.
     */
    public static function endBreak($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $result = \Bookando\Modules\Workday\Services\BreakService::endBreak($userId);

            return Response::ok([
                'success' => true,
                'message' => __('Break ended successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get breaks for a time entry.
     */
    public static function getBreaks($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $timeEntryId = isset($params['id']) ? (int) $params['id'] : 0;

            if (!$timeEntryId) {
                return Response::error(__('Time entry ID is required', 'bookando'), 400);
            }

            $breaks = \Bookando\Modules\Workday\Services\BreakService::getBreaksForTimeEntry($timeEntryId);

            return Response::ok([
                'success' => true,
                'data' => ['breaks' => $breaks],
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    // =====================================================
    // Shift Management Handlers
    // =====================================================

    /**
     * Get shifts.
     */
    public static function getShifts($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $startDate = $request->get_param('start_date') ?? date('Y-m-01');
            $endDate = $request->get_param('end_date') ?? date('Y-m-t');

            $filters = [
                'user_id' => $request->get_param('user_id') ? (int) $request->get_param('user_id') : null,
                'status' => $request->get_param('status'),
                'shift_type' => $request->get_param('shift_type'),
                'location_id' => $request->get_param('location_id') ? (int) $request->get_param('location_id') : null,
            ];

            $shifts = \Bookando\Modules\Workday\Services\ShiftService::getShifts(
                $startDate,
                $endDate,
                array_filter($filters)
            );

            return Response::ok([
                'success' => true,
                'data' => ['shifts' => $shifts],
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create a shift.
     */
    public static function createShift($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];

            $shift = \Bookando\Modules\Workday\Services\ShiftService::createShift($body);

            return Response::ok([
                'success' => true,
                'message' => __('Shift created successfully', 'bookando'),
                'data' => $shift,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update a shift.
     */
    public static function updateShift($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $shiftId = isset($params['id']) ? (int) $params['id'] : 0;
            $body = $request->get_json_params() ?? [];

            if (!$shiftId) {
                return Response::error(__('Shift ID is required', 'bookando'), 400);
            }

            $shift = \Bookando\Modules\Workday\Services\ShiftService::updateShift($shiftId, $body);

            return Response::ok([
                'success' => true,
                'message' => __('Shift updated successfully', 'bookando'),
                'data' => $shift,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a shift.
     */
    public static function deleteShift($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $shiftId = isset($params['id']) ? (int) $params['id'] : 0;

            if (!$shiftId) {
                return Response::error(__('Shift ID is required', 'bookando'), 400);
            }

            $result = \Bookando\Modules\Workday\Services\ShiftService::deleteShift($shiftId);

            return Response::ok([
                'success' => true,
                'message' => __('Shift deleted successfully', 'bookando'),
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Publish shifts.
     */
    public static function publishShifts($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $body = $request->get_json_params() ?? [];
            $shiftIds = $body['shift_ids'] ?? [];

            if (empty($shiftIds)) {
                return Response::error(__('No shift IDs provided', 'bookando'), 400);
            }

            $result = \Bookando\Modules\Workday\Services\ShiftService::publishShifts(
                $shiftIds,
                get_current_user_id()
            );

            return Response::ok([
                'success' => true,
                'message' => __('Shifts published successfully', 'bookando'),
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    // =====================================================
    // Vacation Balance Handlers
    // =====================================================

    /**
     * Get vacation balance.
     */
    public static function getVacationBalance($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userId = isset($params['user_id']) ? (int) $params['user_id'] : 0;
            $year = $request->get_param('year') ? (int) $request->get_param('year') : null;

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $balance = \Bookando\Modules\Workday\Services\VacationBalanceService::getBalance($userId, $year);

            return Response::ok([
                'success' => true,
                'data' => $balance,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update vacation balance.
     */
    public static function updateVacationBalance($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userId = isset($params['user_id']) ? (int) $params['user_id'] : 0;
            $year = isset($params['year']) ? (int) $params['year'] : (int) date('Y');
            $body = $request->get_json_params() ?? [];

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $balance = \Bookando\Modules\Workday\Services\VacationBalanceService::updateBalance(
                $userId,
                $year,
                $body
            );

            return Response::ok([
                'success' => true,
                'message' => __('Vacation balance updated successfully', 'bookando'),
                'data' => $balance,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get vacation statistics.
     */
    public static function getVacationStatistics($params, WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userId = isset($params['user_id']) ? (int) $params['user_id'] : 0;
            $year = $request->get_param('year') ? (int) $request->get_param('year') : (int) date('Y');

            if (!$userId) {
                return Response::error(__('Employee ID is required', 'bookando'), 400);
            }

            $statistics = \Bookando\Modules\Workday\Services\VacationBalanceService::getStatistics($userId, $year);

            return Response::ok([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    }
}
