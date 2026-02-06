# Phase 4B — Kernel Ports & Adapters (Detailspezifikation)

> Alle Infrastructure Ports des Platform Kernels mit Interface-Definitionen und Adapter-Mapping.

---

## 1. PersistencePort

```php
interface PersistencePort
{
    /** Execute SELECT, return rows as associative arrays. */
    public function query(string $sql, array $params = []): array;

    /** Execute SELECT, return single row or null. */
    public function queryRow(string $sql, array $params = []): ?array;

    /** Execute SELECT, return single scalar value. */
    public function queryValue(string $sql, array $params = []): mixed;

    /** Insert row, return generated ID. */
    public function insert(string $table, array $data): int;

    /** Update rows matching conditions, return affected count. */
    public function update(string $table, array $data, array $where): int;

    /** Delete rows matching conditions, return affected count. */
    public function delete(string $table, array $where): int;

    /** Begin database transaction. */
    public function beginTransaction(): void;

    /** Commit current transaction. */
    public function commit(): void;

    /** Rollback current transaction. */
    public function rollback(): void;

    /** Escape a value for safe SQL inclusion (prefer prepared statements). */
    public function escape(string $value): string;

    /** Run callback within a transaction. Auto-rollback on exception. */
    public function transactional(callable $fn): mixed;

    /** Get the table name with appropriate prefix. */
    public function tableName(string $base): string;
}
```

### Adapter-Mapping:
| Adapter | Implementierung |
|---------|----------------|
| WordPress | `WordPressDatabaseAdapter` (existiert: `src/Core/Adapter/WordPressDatabaseAdapter.php`) — nutzt `$wpdb->prepare/insert/update/delete` |
| SaaS (PostgreSQL) | `PdoPersistenceAdapter` — PDO mit prepared statements, `BEGIN/COMMIT/ROLLBACK` |
| SaaS (MySQL) | `PdoPersistenceAdapter` — gleiche Implementierung, anderer DSN |
| PWA (IndexedDB) | `IndexedDbAdapter` (TypeScript) — Dexie.js oder raw IDB |
| Testing | `InMemoryPersistenceAdapter` — Array-basiert, kein DB nötig |

---

## 2. QueuePort

```php
interface QueuePort
{
    /** Enqueue a job for async processing. */
    public function enqueue(string $jobClass, array $payload, int $priority = 5, ?string $uniqueKey = null): int|false;

    /** Enqueue a job with delay. */
    public function enqueueDelayed(string $jobClass, array $payload, int $delaySeconds, int $priority = 5): int|false;

    /** Process next batch of pending jobs. */
    public function process(int $batchSize = 10): int;

    /** Retry a failed/dead job. */
    public function retry(int $jobId): bool;

    /** Get queue statistics. */
    public function stats(): array;

    /** Clean up completed jobs older than N days. */
    public function cleanup(int $olderThanDays = 30): int;
}
```

### Invariante: Jeder Job-Payload MUSS `tenant_id` enthalten.

### Adapter-Mapping:
| Adapter | Implementierung |
|---------|----------------|
| WordPress | `WpQueueAdapter` — nutzt `wp_bookando_queue_jobs` Tabelle + WP-Cron |
| SaaS | `RedisQueueAdapter` — Redis Streams oder Bull-ähnlich |
| Testing | `SyncQueueAdapter` — führt Jobs sofort aus (kein Async) |

---

## 3. CachePort

```php
interface CachePort
{
    /** Get cached value. Returns $default if not found or expired. */
    public function get(string $key, mixed $default = null): mixed;

    /** Set cached value with TTL in seconds. */
    public function set(string $key, mixed $value, int $ttlSeconds = 3600): bool;

    /** Delete cached value. */
    public function delete(string $key): bool;

    /** Check if key exists and is not expired. */
    public function has(string $key): bool;

    /** Delete all cached values matching prefix. */
    public function flush(string $prefix = ''): bool;
}
```

### Invariante: Kernel MUSS Tenant-Prefix automatisch hinzufügen: `{tenant_id}:{key}`

### Adapter-Mapping:
| Adapter | Implementierung |
|---------|----------------|
| WordPress | `WpCacheAdapter` — `get_transient()`/`set_transient()` |
| SaaS | `RedisCacheAdapter` — Redis GET/SET/DEL mit TTL |
| PWA | `LocalStorageCacheAdapter` — localStorage mit TTL-Metadata |
| Testing | `InMemoryCacheAdapter` |

---

## 4. ClockPort

```php
interface ClockPort
{
    /** Current time in UTC. */
    public function now(): DateTimeImmutable;

    /** Current time in specified timezone. */
    public function nowIn(DateTimeZone $tz): DateTimeImmutable;

    /** Current UTC timestamp as string (Y-m-d H:i:s). */
    public function nowUtcString(): string;
}
```

### Adapter-Mapping:
| Adapter | Implementierung |
|---------|----------------|
| WordPress | `WpClockAdapter` — `new DateTimeImmutable('now', new DateTimeZone('UTC'))` (NICHT `current_time()`) |
| SaaS | `SystemClockAdapter` — gleich |
| Testing | `FrozenClockAdapter` — feste Zeit für deterministische Tests |

---

## 5. CryptoPort

```php
interface CryptoPort
{
    /** Hash a password (bcrypt/argon2). */
    public function hashPassword(string $password): string;

    /** Verify password against hash. */
    public function verifyPassword(string $password, string $hash): bool;

    /** Generate HMAC signature. */
    public function hmac(string $data, string $key): string;

    /** Verify HMAC signature (timing-safe). */
    public function verifyHmac(string $data, string $signature, string $key): bool;

    /** Encrypt data (AES-256-GCM). */
    public function encrypt(string $plaintext, string $key): string;

    /** Decrypt data. */
    public function decrypt(string $ciphertext, string $key): string;

    /** Generate cryptographically secure random string. */
    public function randomString(int $length = 32): string;

    /** Generate UUID v4. */
    public function uuid(): string;
}
```

### Adapter-Mapping:
| Adapter | Implementierung |
|---------|----------------|
| WordPress | `WpCryptoAdapter` — `wp_hash_password()`, WP Salt constants, `openssl_encrypt()` |
| SaaS | `NativeCryptoAdapter` — `password_hash()`, env secrets, libsodium |
| PWA | TypeScript: `SubtleCrypto` API |

---

## 6. LoggerPort

```php
interface LoggerPort
{
    /** Log with severity and structured context. */
    public function log(string $level, string $message, array $context = []): void;

    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;

    /** Log audit event (always persisted, never filtered by level). */
    public function audit(string $action, array $context): void;

    /** Set correlation ID for current request/job. */
    public function setCorrelationId(string $correlationId): void;
}
```

### Invarianten:
- Jeder Log-Eintrag enthält automatisch: `timestamp`, `tenant_id`, `correlation_id`, `level`
- Audit-Events sind append-only und haben mindestens 1 Jahr Retention
- Keine PII in regulären Logs (nur in Audit-Events mit Begründung)

---

## 7. IdentityPort

```php
interface IdentityPort
{
    /** Get current authenticated user or null. */
    public function currentUser(): ?UserIdentity;

    /** Check if a user is authenticated. */
    public function isAuthenticated(): bool;

    /** Authenticate with credentials, return identity or null. */
    public function authenticate(string $identifier, string $credential): ?UserIdentity;
}

final class UserIdentity
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly int $tenantId,
        public readonly array $roles,
        public readonly array $permissions,
        public readonly ?string $externalId = null,
        public readonly string $authMethod = 'session',
    ) {}
}
```

---

## 8. AuthorizationPort

```php
interface AuthorizationPort
{
    /** Check if current user has specific permission. */
    public function can(string $permission): bool;

    /** Check if current user can manage a module. */
    public function canManageModule(string $moduleSlug): bool;

    /** Assert permission, throw on failure. */
    public function authorize(string $permission): void;

    /** Check if user is the specified entity owner. */
    public function isSelf(int $userId): bool;
}
```

---

## 9. EventBusPort

```php
interface EventBusPort
{
    /** Publish a domain event. */
    public function publish(DomainEvent $event): void;

    /** Subscribe a listener to an event type. */
    public function subscribe(string $eventType, string $listenerClass): void;

    /** Publish multiple events (typically after a transaction commit). */
    public function publishBatch(array $events): void;
}

interface DomainEvent
{
    public function eventId(): string;
    public function eventType(): string;
    public function tenantId(): int;
    public function occurredAt(): string;
    public function version(): int;
    public function toArray(): array;
}
```

### Regeln:
- Events werden NACH dem Transaction-Commit gepublished (Outbox-Pattern)
- Event-Handler laufen in eigenem Transaction-Scope
- Handler-Failures dürfen Command-Erfolg nicht rückgängig machen (eventual consistency)

---

## 10. PaymentGatewayPort

```php
interface PaymentGatewayPort
{
    public function createPayment(int $amountMinor, string $currency, array $metadata): PaymentResult;
    public function capturePayment(string $externalId): PaymentResult;
    public function refundPayment(string $externalId, int $amountMinor, string $reason = ''): RefundResult;
    public function verifyWebhookSignature(string $payload, array $headers): bool;
    public function handleWebhook(array $data): WebhookResult;
    public function getStatus(string $externalId): string;
}
```

### Invariante: Amounts sind IMMER Integer Minor Units. Keine Float-Konvertierung im Port.

---

## 11. SettingsPort

```php
interface SettingsPort
{
    public function get(string $module, string $key, mixed $default = null, ?int $tenantId = null): mixed;
    public function set(string $module, string $key, mixed $value, ?int $tenantId = null): void;
    public function getAll(string $module, ?int $tenantId = null): array;
    public function delete(string $module, string $key, ?int $tenantId = null): void;
}
```

### Invariante: Alle Settings sind tenant-scoped. Null tenantId = current tenant.
