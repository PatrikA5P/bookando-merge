-- ============================================================================
-- BOOKANDO UNIFIED USERS TABLE
-- ============================================================================
-- This table stores ALL users (Customers, Employees, Admins) in a single table
-- with a roles JSON field to determine user types.
--
-- A user can have multiple roles simultaneously (e.g., both customer and employee)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_bookando_users` (
  -- Primary Key
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Multi-tenancy support',

  -- =========================================================================
  -- CORE USER FIELDS (Shared by all user types)
  -- =========================================================================
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `gender` ENUM('male', 'female', 'other', 'none') DEFAULT NULL,
  `birthday` DATE DEFAULT NULL,

  -- =========================================================================
  -- ADDRESS FIELDS (Shared)
  -- =========================================================================
  `street` VARCHAR(255) DEFAULT NULL COMMENT 'Street address (e.g., 123 Main St)',
  `address_line_2` VARCHAR(255) DEFAULT NULL COMMENT 'Address supplement (Apt, Suite, etc.)',
  `zip` VARCHAR(20) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,

  -- =========================================================================
  -- ROLES & STATUS
  -- =========================================================================
  `roles` JSON NOT NULL DEFAULT '["customer"]' COMMENT 'Array of roles: customer, employee, admin, manager',
  `status` VARCHAR(50) DEFAULT 'active' COMMENT 'active, blocked, deleted, vacation, sick_leave, pause, terminated',

  -- =========================================================================
  -- CUSTOMER-SPECIFIC FIELDS
  -- =========================================================================
  `customer_notes` TEXT DEFAULT NULL COMMENT 'Internal notes about customer',
  `custom_fields` JSON DEFAULT NULL COMMENT 'Custom fields array: [{key, value}, ...]',
  `earned_badges` JSON DEFAULT NULL COMMENT 'Gamification badges: ["badge1", "badge2", ...]',

  -- =========================================================================
  -- EMPLOYEE-SPECIFIC FIELDS
  -- =========================================================================
  `position` VARCHAR(100) DEFAULT NULL COMMENT 'Job title (e.g., Senior Therapist)',
  `department` VARCHAR(100) DEFAULT NULL COMMENT 'Department (e.g., Wellness, Fitness)',
  `hire_date` DATE DEFAULT NULL,
  `exit_date` DATE DEFAULT NULL COMMENT 'Date employee left company',
  `hub_password` VARCHAR(255) DEFAULT NULL COMMENT 'Encrypted password for Partner Hub access',
  `badge_id` VARCHAR(50) DEFAULT NULL COMMENT 'Physical badge ID or access card number',
  `employee_description` TEXT DEFAULT NULL COMMENT 'Employee bio, expertise, certifications',
  `assigned_services` JSON DEFAULT NULL COMMENT 'Array of service IDs employee can provide',
  `avatar_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL to profile picture',

  -- =========================================================================
  -- METADATA
  -- =========================================================================
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT 'User ID who created this record',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT 'User ID who last updated this record',
  `deleted_at` DATETIME DEFAULT NULL COMMENT 'Soft delete timestamp',

  -- =========================================================================
  -- INDEXES
  -- =========================================================================
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`),
  INDEX `idx_roles` ((CAST(`roles` AS CHAR(100)))),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_deleted_at` (`deleted_at`),

  -- Unique constraint per tenant
  UNIQUE KEY `unique_email_per_tenant` (`tenant_id`, `email`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unified users table for customers, employees, and admins';

-- ============================================================================
-- HELPER VIEWS
-- ============================================================================

-- View: Active Customers
CREATE OR REPLACE VIEW `vw_bookando_customers` AS
SELECT
  `id`,
  `tenant_id`,
  `first_name`,
  `last_name`,
  `email`,
  `phone`,
  `gender`,
  `birthday`,
  `street`,
  `address_line_2`,
  `zip`,
  `city`,
  `country`,
  `status`,
  `customer_notes` AS `notes`,
  `custom_fields`,
  `earned_badges`,
  `created_at`,
  `updated_at`
FROM `wp_bookando_users`
WHERE JSON_CONTAINS(`roles`, '"customer"')
  AND `deleted_at` IS NULL;

-- View: Active Employees
CREATE OR REPLACE VIEW `vw_bookando_employees` AS
SELECT
  `id`,
  `tenant_id`,
  `first_name`,
  `last_name`,
  `email`,
  `phone`,
  `gender`,
  `birthday`,
  `street`,
  `address_line_2`,
  `zip`,
  `city`,
  `country`,
  `position`,
  `department`,
  `hire_date`,
  `exit_date`,
  `status`,
  `badge_id`,
  `employee_description` AS `description`,
  `assigned_services`,
  `avatar_url`,
  `customer_notes` AS `notes`, -- Some employees might also have customer notes
  `created_at`,
  `updated_at`
FROM `wp_bookando_users`
WHERE JSON_CONTAINS(`roles`, '"employee"')
  AND `deleted_at` IS NULL;

-- ============================================================================
-- MIGRATION NOTES
-- ============================================================================
--
-- To migrate from separate tables (wp_bookando_customers, wp_bookando_employees):
--
-- 1. Run migration script: database/migrations/migrate_to_unified_users.php
-- 2. Verify data integrity
-- 3. Update REST API endpoints to use new table
-- 4. Drop old tables (backup first!)
--
-- ============================================================================
