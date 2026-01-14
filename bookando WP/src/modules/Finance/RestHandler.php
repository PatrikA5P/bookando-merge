<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance;

use Bookando\Core\Api\Response;
use Bookando\Core\Dispatcher\RestModuleGuard;
use Bookando\Core\Util\Sanitizer;
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

    public static function invoices(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::saveInvoice($request);
        }

        if ($method === 'DELETE') {
            if (!empty($params['subkey'])) {
                $request->set_param('id', $params['subkey']);
            }
            return self::deleteInvoice($request);
        }

        if ($method === 'GET') {
            $state = StateRepository::getState();
            return Response::ok($state['invoices'] ?? []);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function credit_notes(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::saveCreditNote($request);
        }

        if ($method === 'DELETE') {
            if (!empty($params['subkey'])) {
                $request->set_param('id', $params['subkey']);
            }
            return self::deleteCreditNote($request);
        }

        if ($method === 'GET') {
            $state = StateRepository::getState();
            return Response::ok($state['credit_notes'] ?? []);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function discount_codes(array $params, WP_REST_Request $request): WP_REST_Response
    {
        if ($error = self::ensureModuleAllowed()) {
            return $error;
        }

        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::saveDiscountCode($request);
        }

        if ($method === 'DELETE') {
            if (!empty($params['subkey'])) {
                $request->set_param('id', $params['subkey']);
            }
            return self::deleteDiscountCode($request);
        }

        if ($method === 'GET') {
            $state = StateRepository::getState();
            return Response::ok($state['discount_codes'] ?? []);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function settings(array $params, WP_REST_Request $request): WP_REST_Response
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

        return self::saveSettings($request);
    }

    public static function export($params, WP_REST_Request $request): WP_REST_Response
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

        return self::exportLedger($request);
    }

    public static function getState(): WP_REST_Response
    {
        return Response::ok(StateRepository::getState());
    }

    public static function saveInvoice(WP_REST_Request $request): WP_REST_Response
    {
        $payload = self::decodeBody($request);
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Rechnungsdaten.', 'bookando'),
            ], 400);
        }

        $invoice = StateRepository::upsertInvoice($payload, false);
        return Response::created($invoice);
    }

    public static function deleteInvoice(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        $deleted = StateRepository::deleteInvoice($id, false);
        return Response::ok([
            'deleted' => $deleted,
            'id'      => $id,
        ]);
    }

    public static function saveCreditNote(WP_REST_Request $request): WP_REST_Response
    {
        $payload = self::decodeBody($request);
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Gutschriftsdaten.', 'bookando'),
            ], 400);
        }

        $note = StateRepository::upsertInvoice($payload, true);
        return Response::created($note);
    }

    public static function deleteCreditNote(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        $deleted = StateRepository::deleteInvoice($id, true);
        return Response::ok([
            'deleted' => $deleted,
            'id'      => $id,
        ]);
    }

    public static function saveDiscountCode(WP_REST_Request $request): WP_REST_Response
    {
        $payload = self::decodeBody($request);
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Rabattcode-Daten.', 'bookando'),
            ], 400);
        }

        $code = StateRepository::upsertDiscountCode($payload);
        return Response::created($code);
    }

    public static function deleteDiscountCode(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        $deleted = StateRepository::deleteDiscountCode($id);
        return Response::ok([
            'deleted' => $deleted,
            'id'      => $id,
        ]);
    }

    public static function saveSettings(WP_REST_Request $request): WP_REST_Response
    {
        $payload = self::decodeBody($request);
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Einstellungen.', 'bookando'),
            ], 400);
        }
        $settings = StateRepository::setSettings($payload);
        return Response::ok($settings);
    }

    public static function exportLedger(WP_REST_Request $request): WP_REST_Response
    {
        $payload = self::decodeBody($request);
        $fromValue = $payload['from'] ?? null;
        $toValue = $payload['to'] ?? null;
        $from = is_scalar($fromValue) ? Sanitizer::date((string) $fromValue) : null;
        $to = is_scalar($toValue) ? Sanitizer::date((string) $toValue) : null;
        $export = StateRepository::getLedgerExport($from ?: null, $to ?: null);
        return Response::ok($export);
    }

    private static function decodeBody(WP_REST_Request $request)
    {
        $payload = json_decode($request->get_body(), true);
        if (!is_array($payload)) {
            $payload = $request->get_json_params();
        }
        return $payload;
    }

    /**
     * @return bool|WP_Error
     */
    public static function canManage()
    {
        if (!current_user_can('manage_bookando_finance')) {
            return false;
        }

        $error = self::ensureModuleAllowed();

        return $error ?: true;
    }

    private static function ensureModuleAllowed(): ?WP_Error
    {
        if (LicenseManager::isModuleAllowed('finance')) {
            return null;
        }

        return new WP_Error(
            'module_not_allowed',
            __('Das Finanzmodul ist für deinen Tarif nicht verfügbar.', 'bookando'),
            ['status' => 403]
        );
    }
}

