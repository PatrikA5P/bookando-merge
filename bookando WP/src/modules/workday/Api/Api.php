<?php

declare(strict_types=1);

namespace Bookando\Modules\workday\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\workday\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'workday'; }
    protected static function getBaseRoute(): string     { return '/workday'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        // Main workday endpoint
        static::registerRoute('', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('workday'),
        ]);

        // =============================================
        // Workforce Time Tracking Endpoints
        // =============================================

        static::registerRoute('time-tracking', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getTimeTrackingState'),
        ]);

        static::registerRoute('time-tracking/employees', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getActiveEmployees'),
        ]);

        static::registerRoute('time-tracking/clock-in', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('clockIn'),
        ]);

        static::registerRoute('time-tracking/clock-out', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('clockOut'),
        ]);

        static::registerRoute('time-tracking/manual', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createManualEntry'),
        ]);

        // =============================================
        // Vacation Request Endpoints
        // =============================================

        static::registerRoute('vacation-requests', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationRequests'),
        ]);

        static::registerRoute('vacation-requests', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createVacationRequest'),
        ]);

        static::registerRoute('vacation-requests/(?P<id>\d+)/approve', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('approveVacationRequest'),
        ]);

        static::registerRoute('vacation-requests/(?P<id>\d+)/reject', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('rejectVacationRequest'),
        ]);

        static::registerRoute('vacation-requests/(?P<id>\d+)/cancel', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('cancelVacationRequest'),
        ]);

        static::registerRoute('vacation-requests/overview/(?P<user_id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationOverview'),
        ]);

        // =============================================
        // Calendar Endpoints
        // =============================================

        static::registerRoute('calendar', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getCalendar'),
        ]);

        // =============================================
        // Duty Scheduling Endpoints
        // =============================================

        static::registerRoute('duty-scheduling', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getDutySchedulerState'),
        ]);

        static::registerRoute('duty-scheduling/templates', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('saveDutyTemplate'),
        ]);

        static::registerRoute('duty-scheduling/availability', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('saveDutyAvailability'),
        ]);

        static::registerRoute('duty-scheduling/constraints', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('saveDutyConstraints'),
        ]);

        static::registerRoute('duty-scheduling/generate', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('generateDutyRoster'),
        ]);

        // =============================================
        // Break Tracking Endpoints
        // =============================================

        static::registerRoute('breaks/start', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('startBreak'),
        ]);

        static::registerRoute('breaks/end', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('endBreak'),
        ]);

        static::registerRoute('time-entries/(?P<id>\d+)/breaks', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getBreaks'),
        ]);

        // =============================================
        // Shift Management Endpoints
        // =============================================

        static::registerRoute('shifts', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getShifts'),
        ]);

        static::registerRoute('shifts', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createShift'),
        ]);

        static::registerRoute('shifts/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => static::restCallback('updateShift'),
        ]);

        static::registerRoute('shifts/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => static::restCallback('deleteShift'),
        ]);

        static::registerRoute('shifts/publish', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('publishShifts'),
        ]);

        // =============================================
        // Vacation Balance Endpoints
        // =============================================

        static::registerRoute('vacation-balance/(?P<user_id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationBalance'),
        ]);

        static::registerRoute('vacation-balance/(?P<user_id>\d+)/(?P<year>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationBalance'),
        ]);

        static::registerRoute('vacation-balance/(?P<user_id>\d+)/(?P<year>\d+)', [
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => static::restCallback('updateVacationBalance'),
        ]);

        static::registerRoute('vacation-statistics/(?P<user_id>\d+)/(?P<year>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationStatistics'),
        ]);

        // =============================================
        // Appointments (Future)
        // =============================================

        static::registerRoute('appointments', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getAppointments'),
        ]);

        static::registerRoute('appointments', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createAppointment'),
        ]);
    }
}
