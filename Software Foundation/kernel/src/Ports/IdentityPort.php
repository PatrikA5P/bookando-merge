<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Represents the identity of an authenticated user.
 *
 * This is a simple, immutable value object carrying the essential identity
 * attributes needed by the kernel and its modules.
 */
final class UserIdentity
{
    /**
     * @param string   $id          Unique user identifier.
     * @param string   $email       User's email address.
     * @param int      $tenantId    ID of the tenant this user belongs to.
     * @param string[] $roles       List of role slugs assigned to this user.
     * @param string[] $permissions List of permission slugs granted to this user.
     * @param string   $authMethod  Authentication method used (e.g. "session", "api_key", "oauth").
     */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly int $tenantId,
        public readonly array $roles,
        public readonly array $permissions,
        public readonly string $authMethod,
    ) {}
}

/**
 * Identity / authentication port.
 *
 * Provides a host-agnostic interface for resolving the currently authenticated
 * user and for performing credential-based authentication. Implementations
 * adapt to the host's session and user system (e.g. WordPress `wp_get_current_user`,
 * Symfony Security, Laravel Auth, etc.).
 */
interface IdentityPort
{
    /**
     * Return the currently authenticated user, or null if no user is logged in.
     *
     * @return UserIdentity|null The current user's identity, or null for guests.
     */
    public function currentUser(): ?UserIdentity;

    /**
     * Check whether a user is currently authenticated.
     *
     * @return bool True if a user is authenticated in the current request.
     */
    public function isAuthenticated(): bool;

    /**
     * Attempt to authenticate a user by identifier and credential.
     *
     * The identifier is typically an email or username; the credential is
     * typically a password. Implementations MUST NOT reveal whether the
     * identifier or the credential was wrong (to prevent user enumeration).
     *
     * @param string $identifier User identifier (email, username, etc.).
     * @param string $credential User credential (password, token, etc.).
     *
     * @return UserIdentity|null The authenticated user, or null on failure.
     */
    public function authenticate(string $identifier, string $credential): ?UserIdentity;
}
