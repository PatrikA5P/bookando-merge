# Bookando Database Documentation

## Overview

This directory contains database schemas, migrations, and seed data for the Bookando plugin.

## Directory Structure

```
database/
├── schemas/              # SQL schema definitions
│   └── wp_bookando_users.sql
├── migrations/           # Migration scripts
│   └── migrate_to_unified_users.php
├── seeds/               # Test/seed data
│   └── test_users.sql
└── README.md           # This file
```

## Unified Users Table Architecture

### Concept

**All users (Customers, Employees, Admins) are stored in a single `wp_bookando_users` table.**

**Benefits:**
- ✅ A user can have multiple roles simultaneously (e.g., employee who is also a customer)
- ✅ No data duplication (email, name, address shared)
- ✅ Simplified user management
- ✅ Follows WordPress conventions (similar to `wp_users`)

### Table: `wp_bookando_users`

**Shared Fields** (all user types):
- `id`, `tenant_id`
- `first_name`, `last_name`, `email`, `phone`
- `gender`, `birthday`
- `street`, `address_line_2`, `zip`, `city`, `country`
- `roles` (JSON array: `["customer"]`, `["employee"]`, `["customer","employee"]`)
- `status` (active, blocked, deleted, vacation, sick_leave, pause, terminated)

**Customer-Specific Fields:**
- `customer_notes`
- `custom_fields` (JSON)
- `earned_badges` (JSON)

**Employee-Specific Fields:**
- `position`, `department`
- `hire_date`, `exit_date`
- `hub_password` (encrypted)
- `badge_id`
- `employee_description`
- `assigned_services` (JSON)
- `avatar_url`

### Helper Views

**`vw_bookando_customers`** - Filters users with `"customer"` role
**`vw_bookando_employees`** - Filters users with `"employee"` role

## Migration Process

### Step 1: Backup Existing Data

```bash
# Create backup of existing tables
mysqldump -u username -p database_name wp_bookando_customers > customers_backup.sql
mysqldump -u username -p database_name wp_bookando_employees > employees_backup.sql
```

### Step 2: Run Migration

**Option A: Via WP-CLI (recommended)**
```bash
cd /path/to/bookando-wp
wp eval-file database/migrations/migrate_to_unified_users.php
```

**Option B: Direct PHP execution**
```bash
# Ensure ABSPATH is defined first
php -r "define('ABSPATH', '/path/to/wordpress/'); require 'database/migrations/migrate_to_unified_users.php';"
```

**Option C: Via WordPress Admin**
Create a temporary admin page that includes the migration script.

### Step 3: Verify Migration

```sql
-- Check migrated data
SELECT COUNT(*) as total FROM wp_bookando_users;

SELECT
  COUNT(*) as total_users,
  SUM(JSON_CONTAINS(roles, '"customer"')) as customers,
  SUM(JSON_CONTAINS(roles, '"employee"')) as employees,
  SUM(JSON_CONTAINS(roles, '"customer"') AND JSON_CONTAINS(roles, '"employee"')) as dual_roles
FROM wp_bookando_users;

-- Verify views work
SELECT * FROM vw_bookando_customers LIMIT 5;
SELECT * FROM vw_bookando_employees LIMIT 5;
```

### Step 4: Update REST API Endpoints

After successful migration, update:
- `src/modules/Customers/RestHandler.php`
- `src/modules/Employees/RestHandler.php`

Change table references from `wp_bookando_customers` / `wp_bookando_employees` to `wp_bookando_users`.

Filter by roles:
```php
// Get customers
$wpdb->get_results("
  SELECT * FROM {$wpdb->prefix}bookando_users
  WHERE JSON_CONTAINS(roles, '\"customer\"')
  AND deleted_at IS NULL
");

// Get employees
$wpdb->get_results("
  SELECT * FROM {$wpdb->prefix}bookando_users
  WHERE JSON_CONTAINS(roles, '\"employee\"')
  AND deleted_at IS NULL
");
```

### Step 5: Drop Old Tables (AFTER verification!)

```sql
-- ONLY after thorough testing!
DROP TABLE IF EXISTS wp_bookando_customers;
DROP TABLE IF EXISTS wp_bookando_employees;
```

## Test Data

Load test data for development:

```bash
# Via MySQL CLI
mysql -u username -p database_name < database/seeds/test_users.sql

# Via WP-CLI
wp db query < database/seeds/test_users.sql
```

This creates:
- 5 customers only
- 5 employees only
- 2 dual-role users (customer + employee)

All test emails end with `@test.bookando.local` for easy identification.

## Field Naming Conventions

### Address Fields

✅ **Correct:**
- `street` (e.g., "123 Main Street")
- `address_line_2` (e.g., "Apt 5B", "Suite 200")
- `zip`, `city`, `country`

❌ **Deprecated:**
- `address` (renamed to `street`)
- `address_2` (renamed to `address_line_2`)

### Migration Compatibility

The migration script handles old field names:
```php
'street' => $customer->street ?? $customer->address ?? null,
'address_line_2' => $customer->address_line_2 ?? null,
```

## Querying Users by Role

### Get All Customers
```sql
SELECT * FROM vw_bookando_customers;
-- OR
SELECT * FROM wp_bookando_users
WHERE JSON_CONTAINS(roles, '"customer"')
AND deleted_at IS NULL;
```

### Get All Employees
```sql
SELECT * FROM vw_bookando_employees;
-- OR
SELECT * FROM wp_bookando_users
WHERE JSON_CONTAINS(roles, '"employee"')
AND deleted_at IS NULL;
```

### Get Users with Dual Roles
```sql
SELECT * FROM wp_bookando_users
WHERE JSON_CONTAINS(roles, '"customer"')
  AND JSON_CONTAINS(roles, '"employee"')
AND deleted_at IS NULL;
```

### Add Role to Existing User
```sql
UPDATE wp_bookando_users
SET roles = JSON_MERGE_PRESERVE(roles, JSON_ARRAY('employee'))
WHERE id = 123;
```

### Remove Role from User
```sql
UPDATE wp_bookando_users
SET roles = JSON_REMOVE(roles, JSON_UNQUOTE(JSON_SEARCH(roles, 'one', 'customer')))
WHERE id = 123;
```

## Troubleshooting

### Migration fails with "Table already exists"

The script uses `CREATE TABLE IF NOT EXISTS` and `CREATE OR REPLACE VIEW`, so this shouldn't happen. If it does:

```sql
DROP TABLE IF EXISTS wp_bookando_users;
DROP VIEW IF EXISTS vw_bookando_customers;
DROP VIEW IF EXISTS vw_bookando_employees;
```

Then re-run migration.

### Duplicate emails

The unified table has a unique constraint on `(tenant_id, email)`. If migration fails due to duplicates:

1. Find duplicates:
```sql
SELECT email, COUNT(*) FROM wp_bookando_customers GROUP BY email HAVING COUNT(*) > 1;
```

2. Resolve manually (merge or delete duplicates)

3. Re-run migration

### JSON column queries don't work

Ensure MySQL version >= 5.7 or MariaDB >= 10.2 (JSON support required).

```sql
SELECT VERSION();
```

## Support

For issues or questions, contact the development team or create an issue in the repository.

---

**Last Updated:** 2026-01-22
**Schema Version:** 1.0.0
