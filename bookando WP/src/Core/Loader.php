<?php

declare(strict_types=1);

namespace Bookando\Core;

use Bookando\Core\Api\AuthApi;
use Bookando\Core\Api\PartnershipApi;
use Bookando\Core\Api\RolesApi;
use Bookando\Core\Auth\AuthMiddleware;
use Bookando\Core\Manager\ModuleManager;
use Bookando\Core\Helper\HelperPathResolver;
use Bookando\Core\Service\ActivityLogger;

class Loader
{
    public function __construct()
    {
        // Reserviert für spätere Core-Services
    }

    public function init()
    {
        $debugEnabled = $this->isDebugLoggingEnabled();
        if ($debugEnabled) {
            ActivityLogger::info(
                'core.loader',
                'Loader initialisiert',
                ['bookando_dev' => $debugEnabled]
            );
        }

        // 🔐 Authentication Middleware (muss vor Dispatcher kommen)
        $this->initAuth();

        // Dispatcher (REST, AJAX, Webhook)
        $this->initDispatchers();

        // Globale Helper-Funktionen
        $this->initHelpers();

        // 🔧 Module laden und registrieren
        $this->initModules();

        // 📡 Globale REST-APIs registrieren
        $this->initApiRoutes();
    }

    /**
     * Initialisiert Multi-Layer Authentication Middleware.
     * Unterstützt: JWT, API Keys, WordPress Sessions.
     */
    protected function initAuth(): void
    {
        // Registriere REST Pre-Dispatch Hook für Authentifizierung
        add_filter('rest_pre_dispatch', [AuthMiddleware::class, 'authenticate'], 10, 3);

        // Registriere Cron für API Key Last-Used Update
        add_action('bookando_update_api_key_usage', [$this, 'updateApiKeyUsage']);

        if ($this->isDebugLoggingEnabled()) {
            ActivityLogger::info(
                'core.loader.auth',
                'Authentication Middleware registriert',
                ['supports' => ['jwt', 'api_key', 'session']]
            );
        }
    }

    /**
     * Aktualisiert Last-Used-Timestamp für API Key (asynchron via Cron).
     */
    public function updateApiKeyUsage(int $apiKeyId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_api_keys';

        $wpdb->update(
            $table,
            ['last_used_at' => current_time('mysql', 1)],
            ['id' => $apiKeyId],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Lädt alle Dispatcher-Klassen (AJAX, REST, Webhook).
     */
    protected function initDispatchers()
    {
        $dispatcherPath = plugin_dir_path(BOOKANDO_PLUGIN_FILE) . 'src/Core/Dispatcher/';
        $debugEnabled = $this->isDebugLoggingEnabled();

        foreach (['AjaxDispatcher.php', 'RestDispatcher.php', 'WebhookDispatcher.php'] as $file) {
            $full = $dispatcherPath . $file;

            if (file_exists($full)) {
                require_once $full;
                if ($debugEnabled) {
                    ActivityLogger::info(
                        'core.loader.dispatcher',
                        'Dispatcher geladen',
                        [
                            'dispatcher_file' => $file,
                            'dispatcher_path' => $full,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Bindet globale Helper-Funktionen ein.
     */
    protected function initHelpers()
    {
        $debugEnabled = $this->isDebugLoggingEnabled() || (defined('WP_DEBUG') && WP_DEBUG);

        foreach (HelperPathResolver::candidates() as $candidate) {
            if (!is_string($candidate) || !file_exists($candidate)) {
                continue;
            }

            require_once $candidate;

            if ($debugEnabled) {
                ActivityLogger::info(
                    'core.loader.helpers',
                    'Helpers geladen',
                    ['helper_path' => $candidate]
                );
            }
            break;
        }
    }

    /**
     * Initialisiert und lädt alle aktivierten Module gemäß Lizenz.
     */
    protected function initModules(): void
    {
        $managerPath = plugin_dir_path(BOOKANDO_PLUGIN_FILE) . 'src/Core/Manager/ModuleManager.php';
        $debugEnabled = $this->isDebugLoggingEnabled();

        if (file_exists($managerPath)) {
            require_once $managerPath;

            $manager = ModuleManager::instance();
            $manager->loadModules();

            if ($debugEnabled) {
                ActivityLogger::info(
                    'core.loader.modules',
                    'ModuleManager: Module geladen',
                    ['manager_path' => $managerPath]
                );
            }
        } else {
            ActivityLogger::warning(
                'core.loader.modules',
                'ModuleManager.php nicht gefunden',
                ['manager_path' => $managerPath]
            );
        }
    }

    /**
     * Registriert zentrale REST-Routen wie /roles, /auth, /partnerships
     */
    protected function initApiRoutes(): void
    {
        add_action('rest_api_init', function () {
            AuthApi::register();
            PartnershipApi::register();
            RolesApi::register();
        });
    }

    private function isDebugLoggingEnabled(): bool
    {
        return defined('BOOKANDO_DEV') ? (bool) BOOKANDO_DEV : false;
    }
}
