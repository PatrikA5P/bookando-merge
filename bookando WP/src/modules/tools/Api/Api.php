<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\tools\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'tools'; }
    protected static function getBaseRoute(): string     { return '/tools'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        // Main tools endpoint
        static::registerRoute('', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('tools'),
        ]);

        // Reports endpoint
        static::registerRoute('reports', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('reports'),
        ]);

        // System info endpoint
        static::registerRoute('system-info', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('systemInfo'),
        ]);

        // Course planner endpoints
        static::registerRoute('course-planner', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getCoursePlannerState'),
        ]);

        static::registerRoute('course-planner/import', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('importCourseHistory'),
        ]);

        static::registerRoute('course-planner/preferences', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('saveCoursePlannerPreferences'),
        ]);

        static::registerRoute('course-planner/generate', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('generateCoursePlan'),
        ]);

        static::registerRoute('course-planner/offers', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getCoursePlannerOffers'),
        ]);

        // Time tracking endpoints
        static::registerRoute('time-tracking', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getTimeTrackingState'),
        ]);

        static::registerRoute('time-tracking/clock-in', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('clockInTimeTracking'),
        ]);

        static::registerRoute('time-tracking/clock-out', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('clockOutTimeTracking'),
        ]);

        static::registerRoute('time-tracking/entries', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createTimeTrackingEntry'),
        ]);

        static::registerRoute('time-tracking/rules', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('saveTimeTrackingRules'),
        ]);

        // Duty scheduling endpoints
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

        // Design Templates endpoints
        static::registerRoute('design/templates', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getDesignTemplates'),
        ]);

        static::registerRoute('design/templates', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createDesignTemplate'),
        ]);

        static::registerRoute('design/templates/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => static::restCallback('updateDesignTemplate'),
        ]);

        static::registerRoute('design/templates/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => static::restCallback('deleteDesignTemplate'),
        ]);

        static::registerRoute('design/templates/(?P<id>\d+)/compile', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('compileDesignTemplate'),
        ]);

        // Design Presets endpoint
        static::registerRoute('design/presets', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getDesignPresets'),
        ]);

        // Design Export/Import endpoints
        static::registerRoute('design/templates/(?P<id>\d+)/export', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('exportDesignTemplate'),
        ]);

        static::registerRoute('design/templates/import', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('importDesignTemplate'),
        ]);

        // Accessibility Check endpoint
        static::registerRoute('design/check-contrast', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('checkContrast'),
        ]);

        // =============================================
        // Workforce Management Endpoints (NEW)
        // =============================================

        // Workforce Time Tracking endpoints
        static::registerRoute('workforce/time-tracking', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getWorkforceTimeState'),
        ]);

        static::registerRoute('workforce/time-tracking/employees', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getActiveEmployees'),
        ]);

        static::registerRoute('workforce/time-tracking/clock-in', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('workforceClockIn'),
        ]);

        static::registerRoute('workforce/time-tracking/clock-out', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('workforceClockOut'),
        ]);

        static::registerRoute('workforce/time-tracking/manual', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createWorkforceEntry'),
        ]);

        // Vacation Request endpoints
        static::registerRoute('workforce/vacation-requests', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationRequests'),
        ]);

        static::registerRoute('workforce/vacation-requests', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createVacationRequest'),
        ]);

        static::registerRoute('workforce/vacation-requests/(?P<id>\d+)/approve', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('approveVacationRequest'),
        ]);

        static::registerRoute('workforce/vacation-requests/(?P<id>\d+)/reject', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('rejectVacationRequest'),
        ]);

        static::registerRoute('workforce/vacation-requests/(?P<id>\d+)/cancel', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('cancelVacationRequest'),
        ]);

        static::registerRoute('workforce/vacation-requests/overview/(?P<user_id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getVacationOverview'),
        ]);

        // Calendar data endpoint for graphical calendar
        static::registerRoute('workforce/calendar', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getWorkforceCalendar'),
        ]);

        // Booking Forms endpoints
        static::registerRoute('booking-forms', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getBookingForms'),
        ]);

        static::registerRoute('booking-forms', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createBookingForm'),
        ]);

        static::registerRoute('booking-forms/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getBookingForm'),
        ]);

        static::registerRoute('booking-forms/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => static::restCallback('updateBookingForm'),
        ]);

        static::registerRoute('booking-forms/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => static::restCallback('deleteBookingForm'),
        ]);

        // Notifications endpoints
        static::registerRoute('notifications', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getNotifications'),
        ]);

        static::registerRoute('notifications', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('createNotification'),
        ]);

        static::registerRoute('notifications/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getNotification'),
        ]);

        static::registerRoute('notifications/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => static::restCallback('updateNotification'),
        ]);

        static::registerRoute('notifications/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => static::restCallback('deleteNotification'),
        ]);

        // Notification Logs endpoints
        static::registerRoute('notifications/logs', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getNotificationLogs'),
        ]);

        static::registerRoute('notifications/logs/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('getNotificationLog'),
        ]);
    }
}
