# Database Adapter Layer

**Status:** ✅ Foundation implemented (Phase C6 Part 1)
**Next:** Gradual migration of modules to use adapter

---

## Purpose

This adapter layer decouples Bookando's business logic from WordPress-specific database implementation, enabling the codebase to run in multiple environments:

- **WordPress Plugin** (current) - uses `$wpdb`
- **Standalone SaaS** (future) - uses PDO/Doctrine
- **Docker/Cloud** (future) - any SQL database

---

## Architecture

```
Business Logic (RestHandlers, Services)
         ↓
DatabaseAdapterFactory
         ↓
   ┌─────────────────┐
   │ DatabaseAdapter │ (Interface)
   └────────┬────────┘
            ├─── WordPressDatabaseAdapter (✅ implemented)
            ├─── PDODatabaseAdapter (📋 todo)
            └─── DoctrineDatabaseAdapter (📋 future)
```

---

## Usage

### Basic Query

```php
use Bookando\Core\Adapter\DatabaseAdapterFactory;

$db = DatabaseAdapterFactory::create();

// Query with parameters (automatically uses $wpdb->prepare in WP mode)
$users = $db->query(
    'SELECT * FROM users WHERE tenant_id = %d AND status = %s',
    [$tenantId, 'active']
);
```

### Insert

```php
$userId = $db->insert('users', [
    'tenant_id' => 1,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);

echo "Inserted user ID: {$userId}";
```

### Update

```php
$affected = $db->update(
    'users',
    ['status' => 'inactive'],  // Data to update
    ['id' => 123]               // WHERE condition
);

echo "Updated {$affected} rows";
```

### Delete

```php
$affected = $db->delete(
    'users',
    ['id' => 123, 'tenant_id' => 1]
);
```

### Transactions

```php
$db->beginTransaction();

try {
    $db->insert('users', $userData);
    $db->insert('user_meta', $metaData);
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}
```

---

## Migration Strategy

### Phase 1: New Code (✅ Recommended)
All new features should use the adapter:

```php
// ❌ Old way (WordPress-coupled)
global $wpdb;
$wpdb->get_results("SELECT * FROM {$wpdb->prefix}bookando_users");

// ✅ New way (platform-agnostic)
$db = DatabaseAdapterFactory::create();
$db->query('SELECT * FROM users');
```

### Phase 2: Gradual Refactoring (📋 Planned)
Refactor existing modules one by one:

1. Start with smallest module (resources, partnerhub)
2. Replace `global $wpdb` with `DatabaseAdapterFactory::create()`
3. Test thoroughly
4. Move to next module

Target modules in order:
- resources (smallest, safest)
- partnerhub
- settings
- workday
- ... (others)

### Phase 3: Complete Migration (🎯 Goal)
- No direct `$wpdb` usage in business logic
- All queries go through adapter
- Ready for standalone deployment

---

## Environment Configuration

### WordPress Mode (Default)
No configuration needed. Auto-detects WordPress and uses `$wpdb`.

### Standalone Mode (Future)
Set environment variable:

```bash
BOOKANDO_MODE=standalone
```

---

## Benefits

### 1. Platform Independence
Same codebase runs on WordPress AND standalone SaaS.

### 2. Easier Testing
Mock the adapter for unit tests:

```php
$mockAdapter = new MockDatabaseAdapter();
DatabaseAdapterFactory::setAdapter($mockAdapter);

// Test your service without real database
```

### 3. Better Architecture
- Clear separation of concerns
- Business logic independent of platform
- Easier to maintain and refactor

### 4. Future-Proof
- Easy to add new database backends
- Smooth migration to SaaS/Cloud
- No vendor lock-in

---

## Testing

```php
use Bookando\Core\Adapter\DatabaseAdapterFactory;

// Get adapter
$db = DatabaseAdapterFactory::create();

// Test insert
$id = $db->insert('test_table', ['name' => 'Test']);
assert($id > 0);

// Test query
$rows = $db->query('SELECT * FROM test_table WHERE id = %d', [$id]);
assert(count($rows) === 1);

// Test update
$affected = $db->update('test_table', ['name' => 'Updated'], ['id' => $id]);
assert($affected === 1);

// Test delete
$affected = $db->delete('test_table', ['id' => $id]);
assert($affected === 1);
```

---

## Next Steps

1. ✅ Adapter foundation created (DatabaseAdapter, WordPressDatabaseAdapter, Factory)
2. 📋 Refactor `resources` module to use adapter (reference implementation)
3. 📋 Create PDODatabaseAdapter for standalone mode
4. 📋 Gradually migrate all modules
5. 📋 Remove all direct `$wpdb` usage
6. 📋 Test in standalone mode
7. 🎯 Deploy as SaaS!

---

## Contributing

When adding database queries:
- ✅ **DO** use `DatabaseAdapterFactory::create()`
- ❌ **DON'T** use `global $wpdb` directly
- ✅ **DO** use parameterized queries (`%d`, `%s`)
- ❌ **DON'T** concatenate user input into SQL
- ✅ **DO** include `tenant_id` in all queries (multitenancy!)

---

**Last Updated:** 2026-01-13
**Status:** Foundation complete, migration in progress
