<?php
namespace Bookando\Core\Dispatcher;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Api\Response;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Service\ActivityLogger;
use Bookando\Core\Service\OAuthTokenStorage;
use function __;
use function _x;
use function home_url;
use function wp_get_referer;
use function wp_parse_url;

/**
 * Zentrale REST-Dispatcher-Klasse.
 *
 * Registriert:
 *  - Spezifische Core-Routen (z.B. Avatar-Upload)
 *  - Integrationen (OAuth Start/Callback, Exchange/ICS/Apple Stubs)
 *  - Employees-Subrouten (Workday-Sets, Calendars, Invite)
 *  - Generische Modul-Catch-all-Route (NUR am Ende)
 */
class RestDispatcher
{
    /**
     * Registered module REST handlers. Stored as slug => handler class.
     *
     * @var array<string, string>
     */
    private static array $moduleHandlers = [];

    /**
     * Optional override used in tests to intercept OAuth token persistence.
     *
     * @var callable|null
     */
    private static $tokenPersistenceCallback = null;

    /**
     * Explicit route → module mappings for permission resolution.
     *
     * @var array<string, string>
     */
    private const ROUTE_MODULE_PATTERNS = [
        '#^/bookando/v1/employees/#i'    => 'employees',
        '#^/bookando/v1/integrations/#i' => 'settings',
        '#^/bookando/v1/share(?:$|/)#i'  => 'settings',
    ];

    /**
     * Allows modules to register their REST handler explicitly. This improves
     * discoverability and lets us validate requests early.
     */
    public static function registerModule(string $slug, string $handlerClass): void
    {
        $slug = self::normalizeModuleSlug($slug);
        if ($slug === '') {
            throw new \InvalidArgumentException(
                __('Module slug must not be empty when registering REST handlers.', 'bookando')
            );
        }

        if (!class_exists($handlerClass)) {
            throw new \InvalidArgumentException(
                sprintf(
                    __('REST handler class "%1$s" for module "%2$s" does not exist.', 'bookando'),
                    $handlerClass,
                    $slug
                )
            );
        }

        self::$moduleHandlers[$slug] = $handlerClass;
    }

    /**
     * Returns the registered handler for a module if one exists.
     */
    public static function moduleHandler(string $slug): ?string
    {
        $slug = self::normalizeModuleSlug($slug);
        return self::$moduleHandlers[$slug] ?? null;
    }

    /**
     * Returns all registered module handler slugs.
     *
     * @return string[]
     */
    public static function registeredModules(): array
    {
        return array_keys(self::$moduleHandlers);
    }

    private static function resolveModuleFromRoute(string $route): ?string
    {
        foreach (self::ROUTE_MODULE_PATTERNS as $pattern => $module) {
            if (preg_match($pattern, $route)) {
                return $module;
            }
        }

        foreach (self::registeredModules() as $candidate) {
            if (stripos($route, '/bookando/v1/' . $candidate) === 0) {
                return $candidate;
            }
        }

        return null;
    }

    private static function ensureModuleRegistered(string $module): bool
    {
        return isset(self::$moduleHandlers[$module]);
    }

    private static function normalizeModuleSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
        return is_string($slug) ? $slug : '';
    }

    /** API-Namespace */
    private const NS = 'bookando/v1';

    /** Registriert den REST-Hook. */
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'init']);
    }

    /**
     * Allows tests to override the persistence callback used for OAuth tokens.
     */
    public static function setTokenPersistenceCallback(?callable $callback): void
    {
        self::$tokenPersistenceCallback = $callback;
    }

    /** Entry: alle Routen definieren. Reihenfolge beachten! */
    public static function init(): void
    {
        /**
         * 0) Health Check Routes (keine Authentifizierung, öffentlich)
         *    GET /health, GET /ready
         */
        \Bookando\Core\Api\HealthApi::registerRoutes();

        /**
         * 1) Avatar-Route (muss vor Catch-all kommen)
         *    POST/DELETE /users/{id|self}/avatar
         */
        register_rest_route(self::NS, '/users/(?P<id>\d+|self)/avatar', [
            'methods'             => ['POST', 'DELETE'],
            'callback'            => [self::class, 'avatarHandler'],
            // DEV: offen; PROD: nach Bedarf härten
            'permission_callback' => function ($request) {
                // Dev-Bypass?
                if (defined('BOOKANDO_DEV') && BOOKANDO_DEV) {
                    return true;
                }

                // Login + Nonce prüfen
                if (!is_user_logged_in()) return false;
                $nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
                if (!wp_verify_nonce($nonce, 'wp_rest')) return false;

                $bookando_id = self::resolveUserIdFromParam($request);
                if (!$bookando_id) return false;

                // Admin/Mitarbeitende mit Cap erlaubt
                if (current_user_can('manage_bookando_customers')) {
                    return true;
                }

                // Sonst nur "self"
                global $wpdb;
                $external_id = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT external_id FROM {$wpdb->prefix}bookando_users WHERE id = %d",
                    $bookando_id
                ));
                return $external_id && get_current_user_id() === $external_id;
            },
        ]);

        /**
         * 2) Integrationen / OAuth / Stubs
         *    2.1) OAuth-Start: POST /integrations/oauth/start
         *    2.2) OAuth-Callback: GET /integrations/oauth/callback
         *    2.3) Exchange-Connect (Stub)
         *    2.4) ICS-Validate (Stub)
         *    2.5) Apple/ICS-Save (Stub)
         */
        register_rest_route(self::NS, '/integrations/oauth/start', [
            'methods'             => ['POST'],
            'permission_callback' => [self::class, 'permission'],
            'callback'            => [self::class, 'oauthStart'],
        ]);

        register_rest_route(self::NS, '/integrations/oauth/callback', [
            'methods'             => ['GET'],
            'permission_callback' => [self::class, 'oauthCallbackPermission'],
            'callback'            => [self::class, 'oauthCallback'],
        ]);

        register_rest_route(self::NS, '/integrations/exchange/connect', [
            'methods'             => ['POST'],
            'permission_callback' => [self::class, 'permission'],
            'callback'            => [self::class, 'exchangeConnect'],
        ]);

        register_rest_route(self::NS, '/integrations/ics/validate', [
            'methods'             => ['GET'],
            'permission_callback' => [self::class, 'permission'],
            'callback'            => [self::class, 'icsValidate'],
        ]);

        /**
         * 3) Employees-Subrouten
         *    3.1) Workday-Sets
         *    3.2) Calendar-Connections (ICS)
         *    3.3) Calendars (List/Update/Delete)
         *    3.4) Calendar-Invite
         */
        register_rest_route(self::NS, '/employees/(?P<id>\d+)/workday-sets', [
            'methods'             => ['GET', 'POST'],
            'callback'            => [self::class, 'employeesWorkdaySets'],
            'permission_callback' => [self::class, 'permission'],
        ]);

        register_rest_route(self::NS, '/employees/(?P<id>\d+)/calendar/connections/ics', [
            'methods'             => ['POST', 'DELETE'],
            'callback'            => [self::class, 'employeesCalendarIcs'],
            'permission_callback' => [self::class, 'permission'],
        ]);

        register_rest_route(self::NS, '/employees/(?P<id>\d+)/calendars', [
            'methods'             => ['GET', 'PUT'],
            'callback'            => [self::class, 'employeesCalendarsList'],
            'permission_callback' => [self::class, 'permission'],
        ]);

        register_rest_route(self::NS, '/employees/(?P<id>\d+)/calendars/(?P<calId>\d+)', [
            'methods'             => ['PATCH', 'DELETE'],
            'callback'            => [self::class, 'employeesCalendarsUpdate'],
            'permission_callback' => [self::class, 'permission'],
        ]);

        register_rest_route(self::NS, '/employees/(?P<id>\d+)/calendar/invite', [
            'methods'             => ['POST'],
            'callback'            => [self::class, 'employeesCalendarInvite'],
            'permission_callback' => [self::class, 'permission'],
        ]);

        register_rest_route(self::NS, '/employees/(?P<id>\d+)/days-off', [
            'methods'             => ['GET','POST','PUT'],
            'callback'            => [self::class, 'employeesDaysOff'],
            'permission_callback' => [self::class, 'permission'],
        ]);

        register_rest_route(self::NS, '/employees/(?P<id>\d+)/special-day-sets', [
            'methods'             => ['GET','POST'],
            'callback'            => [self::class, 'employeesSpecialDaySets'],
            'permission_callback' => [self::class, 'permission'],
        ]);


        /**
         * 4) Generische Catch-all-Modulroute – IMMER ZULETZT!
         *    /{module}/{type}/{subkey?}
         */
        register_rest_route(
            self::NS,
            '/(?P<module>(?!users$)[a-zA-Z0-9_-]+)/(?P<type>[a-zA-Z][a-zA-Z0-9_-]*)(?:/(?P<subkey>[a-zA-Z0-9_-]+))?',
            [
                'methods'             => ['GET', 'POST', 'PUT', 'DELETE'],
                'callback'            => [self::class, 'dispatch'],
                'permission_callback' => [self::class, 'permission'],
            ]
        );

        /**
         * 5) Cross-Tenant-Sharing (ACL + Token)
         *     POST /share        → ACL anlegen + Token zurück
         *     GET  /share/resolve?token=... → Token prüfen/auflösen
         */
        register_rest_route(self::NS, '/share', [
            'methods'             => ['POST'],
            'callback'            => [self::class, 'shareCreate'],
            'permission_callback' => [self::class, 'permission'],
        ]);
        register_rest_route(self::NS, '/share/resolve', [
            'methods'             => ['GET'],
            'callback'            => [self::class, 'shareResolve'],
            'permission_callback' => '__return_true', // Token ist signiert; lesend
        ]);
    }

    /* =========================================================================
     * OAuth: Start + Callback
     * ========================================================================= */

    private static function persistOAuthToken(int $employeeId, string $provider, array $payload, string $mode): void
    {
        if (self::$tokenPersistenceCallback !== null) {
            (self::$tokenPersistenceCallback)($employeeId, $provider, $payload, $mode);
            return;
        }

        OAuthTokenStorage::persist($employeeId, $provider, $payload, $mode);
    }

    /**
     * POST /integrations/oauth/start
     * Erwartet: provider ('google'|'microsoft'), employee_id, mode ('ro'|'wb')
     * Antwort:  { auth_url }
     */
    public static function oauthStart(WP_REST_Request $req)
    {
        $provider   = sanitize_text_field($req->get_param('provider') ?: '');
        $employeeId = (int) ($req->get_param('employee_id') ?: 0);
        $mode       = in_array($req->get_param('mode'), ['ro', 'wb'], true) ? $req->get_param('mode') : 'ro';

        if (!$provider || !$employeeId) {
            ActivityLogger::warning('rest.oauth', 'OAuth start missing provider or employee', [
                'provider'    => $provider ?: null,
                'employee_id' => $employeeId,
                'mode'        => $mode,
            ]);
            return new WP_Error(
                'bad_request',
                _x('provider/employee_id missing', 'REST API error message', 'bookando'),
                ['status' => 400]
            );
        }

        ActivityLogger::info('rest.oauth', 'OAuth start initiated', [
            'provider'    => $provider,
            'employee_id' => $employeeId,
            'mode'        => $mode,
        ]);

        $redirect_uri = self::build_redirect_uri('integrations/oauth/callback');

        // CSRF/Context in state hinterlegen
        $state = wp_generate_uuid4();
        set_transient('bookando_oauth_state_' . $state, [
            'provider'    => $provider,
            'employee_id' => $employeeId,
            'mode'        => $mode,
            'created_at'  => time(),
        ], MINUTE_IN_SECONDS * 15);

        // Scopes
        $scopes = ($mode === 'ro')
            ? ['email', 'profile', 'https://www.googleapis.com/auth/calendar.readonly']
            : ['email', 'profile', 'https://www.googleapis.com/auth/calendar'];

        if ($provider === 'google') {
            $client_id = defined('BOOKANDO_GOOGLE_CLIENT_ID')
                ? BOOKANDO_GOOGLE_CLIENT_ID
                : get_option('bookando_google_client_id');

            if (!$client_id) {
                ActivityLogger::error('rest.oauth', 'Missing Google OAuth client configuration', [
                    'provider'    => 'google',
                    'employee_id' => $employeeId,
                ]);
                return new WP_Error(
                    'conf_missing',
                    _x('Missing Google client_id', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            $params = [
                'client_id'              => $client_id,
                'redirect_uri'           => $redirect_uri,
                'response_type'          => 'code',
                'access_type'            => 'offline',
                'include_granted_scopes' => 'true',
                'prompt'                 => 'select_account consent',
                'scope'                  => implode(' ', $scopes),
                'state'                  => $state,
            ];

            $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' .
                http_build_query($params, '', '&', PHP_QUERY_RFC3986);

            ActivityLogger::info('rest.oauth', 'OAuth start URL generated', [
                'provider'    => 'google',
                'employee_id' => $employeeId,
                'mode'        => $mode,
            ]);

            return ['auth_url' => $auth_url];
        }

        if ($provider === 'microsoft') {
            $client_id = defined('BOOKANDO_MS_CLIENT_ID')
                ? BOOKANDO_MS_CLIENT_ID
                : get_option('bookando_ms_client_id');

            $tenant = defined('BOOKANDO_MS_TENANT')
                ? BOOKANDO_MS_TENANT
                : (get_option('bookando_ms_tenant') ?: 'common'); // 'common' = alle MS-Konten

            if (!$client_id) {
                ActivityLogger::error('rest.oauth', 'Missing Microsoft OAuth client configuration', [
                    'provider'    => 'microsoft',
                    'employee_id' => $employeeId,
                ]);
                return new WP_Error(
                    'conf_missing',
                    _x('Missing Microsoft client_id', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            $params = [
                'client_id'     => $client_id,
                'response_type' => 'code',
                'redirect_uri'  => $redirect_uri,
                'response_mode' => 'query',
                'scope'         => 'offline_access openid profile email https://graph.microsoft.com/Calendars.ReadWrite',
                'prompt'        => 'select_account',
                'state'         => $state,
            ];

            $auth_url = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?" .
                http_build_query($params, '', '&', PHP_QUERY_RFC3986);

            ActivityLogger::info('rest.oauth', 'OAuth start URL generated', [
                'provider'    => 'microsoft',
                'employee_id' => $employeeId,
                'mode'        => $mode,
            ]);

            return ['auth_url' => $auth_url];
        }

        ActivityLogger::warning('rest.oauth', 'Unsupported OAuth provider requested', [
            'provider'    => $provider,
            'employee_id' => $employeeId,
        ]);

        return new WP_Error(
            'bad_request',
            _x('unsupported provider', 'REST API error message', 'bookando'),
            ['status' => 400]
        );
    }

    /**
     * GET /integrations/oauth/callback
     * Nimmt ?code & ?state entgegen, tauscht Token, persistiert sie sicher,
     * liefert eine einfache JSON-Antwort für das Popup.
     */
    public static function oauthCallbackPermission(WP_REST_Request $request): bool
    {
        $state = $request->get_param('state');

        if (!is_string($state) || $state === '') {
            ActivityLogger::warning('rest.oauth', 'OAuth callback denied: missing state parameter');
            return false;
        }

        $transientKey = 'bookando_oauth_state_' . $state;
        $context      = get_transient($transientKey);

        if (!is_array($context)) {
            ActivityLogger::warning('rest.oauth', 'OAuth callback denied: unknown state', [
                'state' => $state,
            ]);
            return false;
        }

        if (!self::isTrustedOAuthCallbackOrigin()) {
            ActivityLogger::warning('rest.oauth', 'OAuth callback denied: untrusted origin', [
                'state' => $state,
                'referer' => wp_get_referer() ?: null,
            ]);
            return false;
        }

        return true;
    }

    public static function oauthCallback(WP_REST_Request $req)
    {
        $code  = $req->get_param('code');
        $state = $req->get_param('state');

        if (!$code || !$state) {
            ActivityLogger::warning('rest.oauth', 'OAuth callback missing code or state', [
                'state' => $state ?: null,
            ]);
            return new WP_Error(
                'bad_request',
                _x('code/state missing', 'REST API error message', 'bookando'),
                ['status' => 400]
            );
        }

        $ctx = get_transient('bookando_oauth_state_' . $state);
        delete_transient('bookando_oauth_state_' . $state);

        if (!$ctx || !is_array($ctx)) {
            ActivityLogger::warning('rest.oauth', 'OAuth callback state invalid or expired', [
                'state' => $state,
            ]);
            return new WP_Error(
                'bad_state',
                _x('state invalid/expired', 'REST API error message', 'bookando'),
                ['status' => 400]
            );
        }

        $provider   = $ctx['provider'] ?? '';
        $employeeId = (int) ($ctx['employee_id'] ?? 0);
        $mode       = $ctx['mode'] ?? 'ro';
        $redirect_uri = self::build_redirect_uri('integrations/oauth/callback');

        ActivityLogger::info('rest.oauth', 'OAuth callback received', [
            'provider'    => $provider,
            'employee_id' => $employeeId,
            'mode'        => $mode,
        ]);

        if ($provider === 'google') {
            $client_id     = defined('BOOKANDO_GOOGLE_CLIENT_ID') ? BOOKANDO_GOOGLE_CLIENT_ID : get_option('bookando_google_client_id');
            $client_secret = defined('BOOKANDO_GOOGLE_CLIENT_SECRET') ? BOOKANDO_GOOGLE_CLIENT_SECRET : get_option('bookando_google_client_secret');
            if (!$client_id || !$client_secret) {
                ActivityLogger::error('rest.oauth', 'Google OAuth configuration incomplete', [
                    'provider'    => 'google',
                    'employee_id' => $employeeId,
                ]);
                return new WP_Error(
                    'conf_missing',
                    _x('Google client missing', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            $resp = wp_remote_post('https://oauth2.googleapis.com/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body'    => http_build_query([
                    'code'          => $code,
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'redirect_uri'  => $redirect_uri,
                    'grant_type'    => 'authorization_code',
                ], '', '&', PHP_QUERY_RFC3986),
                'timeout' => 20,
            ]);
            if (is_wp_error($resp)) {
                ActivityLogger::error('rest.oauth', 'Google token exchange failed', [
                    'provider'    => 'google',
                    'employee_id' => $employeeId,
                    'error'       => $resp->get_error_message(),
                ]);
                return $resp;
            }

            $json = json_decode(wp_remote_retrieve_body($resp), true);
            if (empty($json['access_token'])) {
                ActivityLogger::error('rest.oauth', 'Google token response missing access_token', [
                    'provider'    => 'google',
                    'employee_id' => $employeeId,
                ]);
                return new WP_Error(
                    'oauth_failed',
                    _x('No access_token', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            try {
                self::persistOAuthToken($employeeId, 'google', $json, $mode);
                ActivityLogger::info('rest.oauth', 'OAuth token stored', [
                    'provider'      => 'google',
                    'employee_id'   => $employeeId,
                    'mode'          => $mode,
                    'has_refresh'   => !empty($json['refresh_token']),
                    'expires_in'    => $json['expires_in'] ?? null,
                ]);
            } catch (\Throwable $exception) {
                ActivityLogger::error('rest.oauth', 'Failed to persist OAuth token', [
                    'provider'    => 'google',
                    'employee_id' => $employeeId,
                    'error'       => $exception->getMessage(),
                ]);
                return new WP_Error(
                    'oauth_persist_failed',
                    _x('Unable to persist OAuth token', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            return Response::ok([
                'ok'       => true,
                'provider' => 'google',
                'mode'     => $mode,
                'message'  => 'Google erfolgreich verbunden – dieses Fenster kannst du schließen.',
            ], 200);
        }

        if ($provider === 'microsoft') {
            $client_id     = defined('BOOKANDO_MS_CLIENT_ID') ? BOOKANDO_MS_CLIENT_ID : get_option('bookando_ms_client_id');
            $client_secret = defined('BOOKANDO_MS_CLIENT_SECRET') ? BOOKANDO_MS_CLIENT_SECRET : get_option('bookando_ms_client_secret');
            $tenant        = defined('BOOKANDO_MS_TENANT') ? BOOKANDO_MS_TENANT : (get_option('bookando_ms_tenant') ?: 'common');

            if (!$client_id || !$client_secret) {
                ActivityLogger::error('rest.oauth', 'Microsoft OAuth configuration incomplete', [
                    'provider'    => 'microsoft',
                    'employee_id' => $employeeId,
                ]);
                return new WP_Error(
                    'conf_missing',
                    _x('MS client missing', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            $resp = wp_remote_post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body'    => http_build_query([
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'code'          => $code,
                    'redirect_uri'  => $redirect_uri,
                    'grant_type'    => 'authorization_code',
                ], '', '&', PHP_QUERY_RFC3986),
                'timeout' => 20,
            ]);
            if (is_wp_error($resp)) {
                ActivityLogger::error('rest.oauth', 'Microsoft token exchange failed', [
                    'provider'    => 'microsoft',
                    'employee_id' => $employeeId,
                    'error'       => $resp->get_error_message(),
                ]);
                return $resp;
            }

            $json = json_decode(wp_remote_retrieve_body($resp), true);
            if (empty($json['access_token'])) {
                ActivityLogger::error('rest.oauth', 'Microsoft token response missing access_token', [
                    'provider'    => 'microsoft',
                    'employee_id' => $employeeId,
                ]);
                return new WP_Error(
                    'oauth_failed',
                    _x('No access_token', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            try {
                self::persistOAuthToken($employeeId, 'microsoft', $json, $mode);
                ActivityLogger::info('rest.oauth', 'OAuth token stored', [
                    'provider'      => 'microsoft',
                    'employee_id'   => $employeeId,
                    'mode'          => $mode,
                    'has_refresh'   => !empty($json['refresh_token']),
                    'expires_in'    => $json['expires_in'] ?? null,
                ]);
            } catch (\Throwable $exception) {
                ActivityLogger::error('rest.oauth', 'Failed to persist OAuth token', [
                    'provider'    => 'microsoft',
                    'employee_id' => $employeeId,
                    'error'       => $exception->getMessage(),
                ]);
                return new WP_Error(
                    'oauth_persist_failed',
                    _x('Unable to persist OAuth token', 'REST API error message', 'bookando'),
                    ['status' => 500]
                );
            }

            return Response::ok([
                'ok'       => true,
                'provider' => 'microsoft',
                'mode'     => $mode,
                'message'  => 'Microsoft erfolgreich verbunden – dieses Fenster kannst du schließen.',
            ], 200);
        }

        ActivityLogger::warning('rest.oauth', 'OAuth callback with unsupported provider', [
            'provider'    => $provider,
            'employee_id' => $employeeId,
        ]);

        return new WP_Error(
            'bad_request',
            _x('unsupported provider', 'REST API error message', 'bookando'),
            ['status' => 400]
        );
    }

    private static function isTrustedOAuthCallbackOrigin(): bool
    {
        $referer = wp_get_referer();

        if ($referer === false || $referer === '') {
            return true;
        }

        $refererHost = wp_parse_url($referer, PHP_URL_HOST);
        $siteHost    = wp_parse_url(home_url(), PHP_URL_HOST);

        if ($refererHost === null || $siteHost === null) {
            return true;
        }

        return strcasecmp($refererHost, $siteHost) === 0;
    }

    /** POST /integrations/exchange/connect (Stub) */
    public static function exchangeConnect(WP_REST_Request $req)
    {
        $email = sanitize_email($req->get_param('email') ?: '');
        return [
            'ok'          => true,
            'calendar_id' => $email ?: ('exchange-' . wp_generate_password(8, false)),
            'token'       => null,
        ];
    }

    /** GET /integrations/ics/validate (Stub) */
    public static function icsValidate(WP_REST_Request $req)
    {
        $url = esc_url_raw($req->get_param('url') ?: '');
        return ['ok' => !empty($url)];
    }

    /* =========================================================================
     * Employees: Proxys auf Modul-Handler
     * ========================================================================= */

    public static function employeesWorkdaySets($request)
    {
        $params = self::safeParams($request);
        return \Bookando\Modules\employees\RestHandler::workdaySets($params, $request);
    }

    public static function employeesCalendarIcs($request)
    {
        $params = self::safeParams($request);
        return \Bookando\Modules\employees\RestHandler::calendarIcs($params, $request);
    }

    public static function employeesCalendarsList($request)
    {
        $params = self::safeParams($request);
        return \Bookando\Modules\employees\RestHandler::calendars($params, $request);
    }

    public static function employeesCalendarsUpdate($request)
    {
        $params = self::safeParams($request);
        $params['calId'] = self::getRequestParam($request, 'calId');
        return \Bookando\Modules\employees\RestHandler::calendars($params, $request);
    }

    public static function employeesCalendarInvite($request)
    {
        $params = self::safeParams($request);
        return \Bookando\Modules\employees\RestHandler::calendarInvite($params, $request);
    }

    public static function employeesDaysOff($request) {
        $params = self::safeParams($request);
        return \Bookando\Modules\employees\RestHandler::daysOff($params, $request);
    }

    public static function employeesSpecialDaySets($request) {
        $params = self::safeParams($request);
        return \Bookando\Modules\employees\RestHandler::specialDaySets($params, $request);
    }


    /* =========================================================================
     * Catch-all-Dispatcher + Permission
     * ========================================================================= */

    /** Generischer Modul-Dispatch (nur wenn Handler existiert). */
    public static function dispatch($request)
    {
        $module = sanitize_key(self::getRequestParam($request, 'module'));
        $type   = sanitize_key(self::getRequestParam($request, 'type'));
        $subkey = self::getRequestParam($request, 'subkey');

        $params = self::safeParams($request);
        if ($subkey) $params['subkey'] = $subkey;

        $handlerClass = self::moduleHandler($module) ?? "Bookando\\Modules\\{$module}\\RestHandler";
        if (class_exists($handlerClass) && method_exists($handlerClass, $type)) {
            try {
                $method   = new \ReflectionMethod($handlerClass, $type);
                $instance = $method->isStatic() ? null : new $handlerClass();
                $count    = $method->getNumberOfParameters();

                $args = match ($count) {
                    0       => [],
                    1       => [$request],
                    default => [$params, $request],
                };

                return $method->invoke($instance, ...$args);
            } catch (\Throwable $exception) {
                ActivityLogger::error('rest.dispatch', 'Module handler failed', [
                    'module' => $module,
                    'type'   => $type,
                    'error'  => $exception->getMessage(),
                ]);
                return new WP_Error(
                    'internal_server_error',
                    _x('Module handler failed', 'REST API error message', 'bookando'),
                    [
                        'status' => 500,
                    ]
                );
            }
        }

        return new WP_Error(
            'not_found',
            sprintf(
                _x('Handler %1$s::%2$s not found', 'REST API error message', 'bookando'),
                $handlerClass,
                $type
            ),
            ['status' => 404]
        );
    }

    /** Berechtigung: zentral via RestModuleGuard */
    public static function permission($request)
    {
        $module = self::resolveModuleSlug($request);

        if ($module === null || $module === '') {
            return new WP_Error(
                'rest_unknown_module',
                _x('Unable to resolve module for this request', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }

        if (!self::ensureModuleRegistered($module)) {
            return new WP_Error(
                'rest_module_unregistered',
                sprintf(
                    _x('No REST module registered for "%s".', 'REST API error message', 'bookando'),
                    $module
                ),
                ['status' => 500]
            );
        }

        if (!$request instanceof WP_REST_Request) {
            // Legacy callbacks expect a simple truthy value when invoked via dispatch().
            return true;
        }

        $after = self::moduleGuardCallback($module);
        $guard = RestModuleGuard::for($module, $after);

        $result = $guard($request);

        if ($result instanceof WP_Error) {
            return $result;
        }

        return $result !== false;
    }

    private static function resolveModuleSlug($request): ?string
    {
        $module = self::getRequestParam($request, 'module');
        if (is_string($module)) {
            $module = self::normalizeModuleSlug($module);
        } else {
            $module = '';
        }

        if ($module !== '') {
            return $module;
        }

        $route = method_exists($request, 'get_route') ? (string) $request->get_route() : '';
        if ($route !== '') {
            $resolved = self::resolveModuleFromRoute($route);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private static function moduleGuardCallback(string $module): ?callable
    {
        $handlerClass = self::moduleHandler($module);

        if ($handlerClass === null) {
            $candidate = sprintf('Bookando\\Modules\\%s\\RestHandler', $module);
            if (class_exists($candidate)) {
                $handlerClass = $candidate;
            }
        }

        if (is_string($handlerClass) && class_exists($handlerClass)) {
            if (method_exists($handlerClass, 'guardPermissions')) {
                return [$handlerClass, 'guardPermissions'];
            }

            if (method_exists($handlerClass, 'guardCapabilities')) {
                return [$handlerClass, 'guardCapabilities'];
            }
        }

        return null;
    }

    /* =========================================================================
     * Avatar-Handler (POST/DELETE)
     * ========================================================================= */

    public static function avatarHandler($request)
    {
        global $wpdb;

        $user_id = self::resolveUserIdFromParam($request);
        if (!$user_id) {
            return new WP_Error(
                'bad_request',
                _x('Invalid user', 'REST API error message', 'bookando'),
                ['status' => 400]
            );
        }

        $table = $wpdb->prefix . 'bookando_users';

        if ($request->get_method() === 'POST') {
            $files = method_exists($request, 'get_file_params') ? $request->get_file_params() : [];
            if (empty($files['avatar'])) {
                return new WP_Error(
                    'no_file',
                    _x('No file uploaded', 'REST API error message', 'bookando'),
                    ['status' => 400]
                );
            }

            // Typ/Größe absichern
            $type = $files['avatar']['type'] ?? '';
            $size = (int) ($files['avatar']['size'] ?? 0);
            if (!preg_match('#^image/#', $type) || $size <= 0 || $size > 5 * 1024 * 1024) {
                return new WP_Error(
                    'invalid_file',
                    _x('Invalid file type or size', 'REST API error message', 'bookando'),
                    ['status' => 400]
                );
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded = wp_handle_upload($files['avatar'], [
                'test_form' => false,
                'mimes'     => [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png',
                    'gif'          => 'image/gif',
                    'webp'         => 'image/webp',
                ],
            ]);

            if (!empty($uploaded['error'])) {
                return new WP_Error('upload_error', $uploaded['error'], ['status' => 500]);
            }

            $avatar_url = esc_url_raw($uploaded['url']);
            $wpdb->update($table, ['avatar_url' => $avatar_url], ['id' => $user_id]);
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }

            return ['avatar_url' => $avatar_url];
        }

        if ($request->get_method() === 'DELETE') {
            $user = $wpdb->get_row($wpdb->prepare("SELECT avatar_url FROM $table WHERE id = %d", $user_id));
            if ($user && !empty($user->avatar_url)) {
                $upload_dir = wp_get_upload_dir();
                $file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $user->avatar_url);

                // SICHERHEIT: Path-Traversal-Schutz
                // Stelle sicher, dass die Datei wirklich im Upload-Verzeichnis liegt
                if (is_string($file) && $file !== '') {
                    $realFile = realpath($file);
                    $realUpload = realpath($upload_dir['basedir']);

                    // Prüfe ob realpath erfolgreich war und Datei im Upload-Dir liegt
                    if ($realFile !== false && $realUpload !== false) {
                        $uploadPrefix = trailingslashit($realUpload);
                        $isInUploadDir = (strpos($realFile, $uploadPrefix) === 0);

                        if ($isInUploadDir && file_exists($realFile)) {
                            @unlink($realFile);
                        } elseif (!$isInUploadDir) {
                            // Sicherheitswarnung loggen
                            ActivityLogger::error('security.path_traversal', 'Path-Traversal-Versuch blockiert', [
                                'user_id' => $user_id,
                                'file' => $file,
                                'real_file' => $realFile,
                                'upload_dir' => $realUpload,
                                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                            ]);
                        }
                    }
                }
            }
            $wpdb->update($table, ['avatar_url' => ''], ['id' => $user_id]);
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }
            return ['avatar_url' => ''];
        }

        return new WP_Error(
            'method_not_allowed',
            _x('Method not allowed', 'REST API error message', 'bookando'),
            ['status' => 405]
        );
    }

    /* =========================================================================
     * Helpers
     * ========================================================================= */

    /** 'self' → Bookando-User-ID des aktuellen WP-Users auflösen. */
    protected static function resolveUserIdFromParam($request): int
    {
        $raw = self::getRequestParam($request, 'id');
        if ($raw === 'self') {
            global $wpdb;
            $wp = get_current_user_id();
            if (!$wp) return 0;
            $id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bookando_users WHERE external_id = %d LIMIT 1",
                $wp
            ));
            return $id ?: 0;
        }
        return (int) $raw;
    }

    /** Request-Parameter robust extrahieren. */
    public static function getRequestParam($request, $key)
    {
        if ($request instanceof WP_REST_Request) {
            if (method_exists($request, 'get_param')) {
                return $request->get_param($key);
            } elseif (isset($request[$key])) {
                return $request[$key];
            }
        }
        return is_array($request) && isset($request[$key]) ? $request[$key] : null;
    }

    /** Immer ein assoziatives Array mit allen Parametern zurückgeben + _tenant_id injizieren. */
    public static function safeParams($request): array
    {
        $params = [];
        if ($request instanceof WP_REST_Request) {
            $params = $request->get_params();
        } elseif (is_array($request)) {
            $params = $request;
        }

        // Mandant robust bestimmen (Header/Route/Body/Subdomain/Usermeta)
        $tenantId = TenantManager::resolveFromRequest($request);
        if ($tenantId > 0) {
            $params['_tenant_id'] = $tenantId;
            // …und global für Models cachen
            TenantManager::setCurrentTenantId($tenantId);
        }

        return $params ?: [];
    }

    /** Redirect-URI für einen REST-Pfad innerhalb des Namespaces bauen. */
    private static function build_redirect_uri(string $path): string
    {
        // Erwartet z.B. 'integrations/oauth/callback'
        return esc_url_raw(rest_url(self::NS . '/' . ltrim($path, '/')));
    }

    /* =========================================================================
     * Sharing-Handler
     * ========================================================================= */
    public static function shareCreate(WP_REST_Request $req)
    {
        // Feature-Gate: nur wenn Sharing erlaubt
        if (!LicenseManager::isFeatureEnabled('cross_tenant_share')) {
            return new WP_Error(
                'forbidden',
                _x('Sharing feature disabled', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }
        if (!Gate::allow($req, 'settings')) {
            return new WP_Error(
                'forbidden',
                _x('Not allowed', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }
        $resourceType   = sanitize_key($req->get_param('resource_type') ?: '');
        $resourceId     = (int) $req->get_param('resource_id');
        $granteeTenant  = (int) $req->get_param('grantee_tenant');
        $scope          = sanitize_key($req->get_param('scope') ?: 'view');
        $ttlMinutes     = (int) ($req->get_param('ttl_minutes') ?: 0);
        if (!$resourceType || $resourceId <= 0 || $granteeTenant <= 0) {
            return new WP_Error(
                'bad_request',
                _x('Missing resource/grantee', 'REST API error message', 'bookando'),
                ['status' => 400]
            );
        }
        $result = ShareService::createShare($resourceType, $resourceId, $granteeTenant, $scope, $ttlMinutes);
        ActivityLogger::info('rest.share', 'Share token created', [
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'grantee'       => $granteeTenant,
            'scope'         => $scope,
            'ttl_minutes'   => $ttlMinutes,
            'token_preview' => isset($result['token']) ? substr((string) $result['token'], 0, 8) . '…' : null,
        ]);
        return Response::ok($result, 201);
    }

    public static function shareResolve(WP_REST_Request $req)
    {
        $token = (string) $req->get_param('token');
        if ($token === '') {
            return new WP_Error(
                'bad_request',
                _x('token missing', 'REST API error message', 'bookando'),
                ['status' => 400]
            );
        }
        $data = ShareService::resolveToken($token);
        if (!$data['ok']) {
            return new WP_Error(
                'forbidden',
                _x('invalid/expired token', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }
        ActivityLogger::info('rest.share', 'Share token resolved', [
            'type'    => $data['type'] ?? null,
            'id'      => $data['id'] ?? null,
            'owner'   => $data['owner_tenant'] ?? null,
            'grantee' => $data['grantee_tenant'] ?? null,
        ]);
        return Response::ok($data, 200);
    }
}
