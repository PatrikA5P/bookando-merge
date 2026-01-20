<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy;

use Bookando\Core\Api\Response;
use Bookando\Core\Dispatcher\RestModuleGuard;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Bookando\Core\Licensing\LicenseManager;
use function __;

class RestHandler
{
    public static function state($params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        if (strtoupper($request->get_method()) !== 'GET') {
            return Response::error([
                'code'    => 'method_not_allowed',
                'message' => __('Methode nicht unterstützt.', 'bookando'),
            ], 405);
        }

        return self::getState();
    }

    public static function courses(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::saveCourse($request);
        }

        if ($method === 'DELETE') {
            if (!empty($params['subkey'])) {
                $request->set_param('id', $params['subkey']);
            }
            return self::deleteCourse($request);
        }

        if ($method === 'GET') {
            $state = StateRepository::getState();
            return Response::ok($state['courses'] ?? []);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function training_cards(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::saveTrainingCard($request);
        }

        if ($method === 'DELETE') {
            if (!empty($params['subkey'])) {
                $request->set_param('id', $params['subkey']);
            }
            return self::deleteTrainingCard($request);
        }

        if ($method === 'GET') {
            $state = StateRepository::getState();
            return Response::ok($state['training_cards'] ?? []);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function training_cards_progress(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        if (strtoupper($request->get_method()) !== 'POST') {
            return Response::error([
                'code'    => 'method_not_allowed',
                'message' => __('Methode nicht unterstützt.', 'bookando'),
            ], 405);
        }

        return self::updateProgress($request);
    }

    public static function getState(): WP_REST_Response
    {
        return Response::ok(StateRepository::getState());
    }

    public static function saveCourse(WP_REST_Request $request): WP_REST_Response
    {
        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            $payload = $request->get_json_params();
        }
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Kursdaten übermittelt.', 'bookando'),
            ], 400);
        }

        $course = StateRepository::upsertCourse($payload);
        return Response::created($course);
    }

    public static function deleteCourse(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        $deleted = StateRepository::deleteCourse($id);
        return Response::ok([
            'deleted' => (bool) $deleted,
            'id'      => $id,
        ]);
    }

    public static function saveTrainingCard(WP_REST_Request $request): WP_REST_Response
    {
        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            $payload = $request->get_json_params();
        }
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Trainingsdaten übermittelt.', 'bookando'),
            ], 400);
        }

        $card = StateRepository::upsertTrainingCard($payload);
        return Response::created($card);
    }

    public static function updateProgress(WP_REST_Request $request): WP_REST_Response
    {
        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            $payload = $request->get_json_params();
        }

        $id = (string) ($request->get_param('id') ?? ($payload['id'] ?? ''));
        if ($id === '') {
            return Response::error([
                'code'    => 'missing_id',
                'message' => __('Ausbildungskarte fehlt.', 'bookando'),
            ], 400);
        }

        $progress = isset($payload['progress']) ? (float) $payload['progress'] : null;
        if ($progress === null) {
            return Response::error([
                'code'    => 'missing_progress',
                'message' => __('Fortschritt fehlt.', 'bookando'),
            ], 400);
        }

        $success = StateRepository::updateTrainingProgress($id, $progress);
        if (!$success) {
            return Response::error([
                'code'    => 'update_failed',
                'message' => __('Fortschritt konnte nicht aktualisiert werden.', 'bookando'),
            ], 500);
        }

        return Response::ok(['success' => true, 'progress' => $progress]);
    }

    public static function deleteTrainingCard(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        $deleted = StateRepository::deleteTrainingCard($id);
        return new WP_REST_Response(['deleted' => $deleted]);
    }

    public static function packages(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::savePackage($request);
        }

        if ($method === 'GET') {
            $state = StateRepository::getState();
            return Response::ok($state['packages'] ?? []);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function savePackage(WP_REST_Request $request): WP_REST_Response
    {
        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            $payload = $request->get_json_params();
        }
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Paketdaten übermittelt.', 'bookando'),
            ], 400);
        }

        $package = StateRepository::upsertPackage($payload);
        return Response::created($package);
    }

    public static function deletePackage(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        $deleted = StateRepository::deletePackage($id);
        return Response::ok([
            'deleted' => (bool) $deleted,
            'id'      => $id,
        ]);
    }

    /**
     * @return bool|WP_Error
     */
    public static function canManage()
    {
        // Prüfe zuerst manage_options (für Administratoren)
        if (current_user_can('manage_options')) {
            error_log('[Bookando Academy] User has manage_options capability');
            return self::ensureModuleAllowed() ?: true;
        }

        // Dann prüfe academy-spezifische Capability
        if (current_user_can('manage_bookando_academy')) {
            error_log('[Bookando Academy] User has manage_bookando_academy capability');
            return self::ensureModuleAllowed() ?: true;
        }

        error_log('[Bookando Academy] User does not have required capabilities');
        return false;
    }

    private static function ensureModuleAllowed(): ?WP_Error
    {
        if (LicenseManager::isModuleAllowed('academy')) {
            return null;
        }

        return new \WP_Error(
            'module_not_allowed',
            __('Das Academy-Modul ist für deinen Tarif nicht verfügbar.', 'bookando'),
            ['status' => 403]
        );
    }
}

