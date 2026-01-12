<?php
declare(strict_types=1);

namespace Bookando\Core\Security;

/**
 * Base class for module capabilities
 *
 * Provides common functionality for registering capabilities across all modules.
 */
abstract class BaseCapabilities
{
    /**
     * Get all capabilities for this module
     *
     * @return list<string>
     */
    abstract public static function getAll(): array;

    /**
     * Get default roles that should receive these capabilities
     *
     * @return list<string>
     */
    abstract public static function getDefaultRoles(): array;

    /**
     * Get the module slug for filter hooks
     *
     * @return string
     */
    abstract protected static function getModuleSlug(): string;

    /**
     * Register capabilities for default roles
     *
     * This method can be overridden by child classes if custom logic is needed.
     */
    public static function register(): void
    {
        $moduleSlug = static::getModuleSlug();
        $roles = apply_filters("bookando_{$moduleSlug}_cap_roles", static::getDefaultRoles());

        foreach ((array) $roles as $roleName) {
            if (!is_string($roleName) || $roleName === '') {
                continue;
            }

            $role = get_role($roleName);
            if ($role === null) {
                continue;
            }

            foreach (static::getAll() as $cap) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
        }

        // Optional: Log capability registration in dev mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf(
                '[Bookando] Registered %d capabilities for module "%s"',
                count(static::getAll()),
                $moduleSlug
            ));
        }
    }

    /**
     * Remove capabilities from default roles (for uninstall)
     */
    public static function unregister(): void
    {
        foreach (static::getDefaultRoles() as $roleName) {
            if (!is_string($roleName) || $roleName === '') {
                continue;
            }

            $role = get_role($roleName);
            if ($role === null) {
                continue;
            }

            foreach (static::getAll() as $cap) {
                if ($role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    /**
     * Check if a user has any of the module's capabilities
     *
     * @param int|null $user_id User ID (null = current user)
     * @return bool
     */
    public static function userHasAnyCapability(?int $user_id = null): bool
    {
        $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();

        if (!$user) {
            return false;
        }

        foreach (static::getAll() as $cap) {
            if ($user->has_cap($cap)) {
                return true;
            }
        }

        return false;
    }
}
