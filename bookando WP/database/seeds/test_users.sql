-- ============================================================================
-- TEST DATA FOR BOOKANDO UNIFIED USERS TABLE
-- ============================================================================
-- This script inserts realistic test data for development and testing
-- ============================================================================

-- Clear existing test data (optional - comment out in production!)
-- DELETE FROM wp_bookando_users WHERE email LIKE '%@test.bookando.local';

-- ============================================================================
-- CUSTOMERS ONLY (no employee role)
-- ============================================================================

INSERT INTO `wp_bookando_users` (
  `tenant_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `birthday`,
  `street`, `address_line_2`, `zip`, `city`, `country`,
  `roles`, `status`, `customer_notes`, `custom_fields`
) VALUES
-- Customer 1
(1, 'Emma', 'Wilson', 'emma.wilson@test.bookando.local', '+1 555-1001', 'female', '1988-03-15',
 '456 Oak Avenue', 'Apt 3B', '10002', 'New York', 'United States',
 '["customer"]', 'active', 'Prefers morning appointments', '[]'),

-- Customer 2
(1, 'James', 'Anderson', 'james.anderson@test.bookando.local', '+1 555-1002', 'male', '1992-07-22',
 '789 Maple Drive', NULL, '10003', 'New York', 'United States',
 '["customer"]', 'active', 'VIP customer - monthly massage package', '[{"key":"membership","value":"Gold"},{"key":"allergies","value":"None"}]'),

-- Customer 3
(1, 'Sophia', 'Martinez', 'sophia.martinez@test.bookando.local', '+1 555-1003', 'female', '1995-11-08',
 '321 Elm Street', 'Suite 5', '10004', 'Brooklyn', 'United States',
 '["customer"]', 'active', NULL, '[]'),

-- Customer 4
(1, 'Oliver', 'Brown', 'oliver.brown@test.bookando.local', '+1 555-1004', 'male', '1985-05-30',
 '654 Pine Road', NULL, '10005', 'Queens', 'United States',
 '["customer"]', 'blocked', 'Blocked due to no-show history', '[]'),

-- Customer 5
(1, 'Isabella', 'Davis', 'isabella.davis@test.bookando.local', '+1 555-1005', 'female', '1990-09-12',
 '987 Birch Lane', 'Unit 2A', '10006', 'Manhattan', 'United States',
 '["customer"]', 'active', 'Yoga enthusiast, interested in meditation classes', '[]');

-- ============================================================================
-- EMPLOYEES ONLY (no customer role)
-- ============================================================================

INSERT INTO `wp_bookando_users` (
  `tenant_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `birthday`,
  `street`, `address_line_2`, `zip`, `city`, `country`,
  `roles`, `status`,
  `position`, `department`, `hire_date`, `badge_id`, `hub_password`,
  `employee_description`, `avatar_url`, `assigned_services`
) VALUES
-- Employee 1 - Therapist
(1, 'Sarah', 'Jenkins', 'sarah.jenkins@test.bookando.local', '+1 555-2001', 'female', '1990-05-15',
 '123 Pine Lane', NULL, '10001', 'New York', 'United States',
 '["employee"]', 'active',
 'Senior Therapist', 'Wellness', '2020-01-10', 'BADGE-99', '$2y$10$encrypted_password_hash',
 'Expert in deep tissue massage and physiotherapy with 10+ years experience. Certified in sports medicine.',
 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150', '["service_001","service_002"]'),

-- Employee 2 - Instructor
(1, 'Mike', 'Ross', 'mike.ross@test.bookando.local', '+1 555-2002', 'male', '1985-11-22',
 '456 Elm Street', 'Apt 12', '10002', 'New York', 'United States',
 '["employee", "manager"]', 'vacation',
 'Fitness Instructor', 'Fitness', '2021-03-15', 'BADGE-45', '$2y$10$encrypted_password_hash',
 'Certified yoga and pilates instructor. Specializes in beginner-friendly classes.',
 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150', '["service_003"]'),

-- Employee 3 - Nutritionist
(1, 'Lisa', 'Chen', 'lisa.chen@test.bookando.local', '+1 555-2003', 'female', '1993-02-18',
 '789 Oak Avenue', NULL, '10003', 'Brooklyn', 'United States',
 '["employee"]', 'active',
 'Nutritionist', 'Wellness', '2022-06-01', 'BADGE-78', '$2y$10$encrypted_password_hash',
 'Registered dietitian with focus on sports nutrition and weight management.',
 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150', '["service_004"]'),

-- Employee 4 - Manager
(1, 'David', 'Kim', 'david.kim@test.bookando.local', '+1 555-2004', 'male', '1982-08-25',
 '321 Maple Drive', 'Suite 3', '10004', 'Queens', 'United States',
 '["employee", "manager", "admin"]', 'active',
 'Operations Manager', 'Management', '2019-01-05', 'BADGE-01', '$2y$10$encrypted_password_hash',
 'Oversees daily operations and staff management. Background in business administration.',
 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150', '[]'),

-- Employee 5 - Receptionist (on sick leave)
(1, 'Maria', 'Garcia', 'maria.garcia@test.bookando.local', '+1 555-2005', 'female', '1998-04-10',
 '654 Birch Lane', NULL, '10005', 'Manhattan', 'United States',
 '["employee"]', 'sick_leave',
 'Receptionist', 'Front Desk', '2023-02-20', 'BADGE-33', '$2y$10$encrypted_password_hash',
 'Friendly and organized receptionist handling customer inquiries and bookings.',
 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150', '[]');

-- ============================================================================
-- DUAL ROLES (both customer AND employee)
-- ============================================================================

INSERT INTO `wp_bookando_users` (
  `tenant_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `birthday`,
  `street`, `address_line_2`, `zip`, `city`, `country`,
  `roles`, `status`,
  `position`, `department`, `hire_date`, `badge_id`, `hub_password`,
  `employee_description`, `avatar_url`, `assigned_services`,
  `customer_notes`, `custom_fields`
) VALUES
-- Dual Role 1 - Employee who also books services
(1, 'Alex', 'Thompson', 'alex.thompson@test.bookando.local', '+1 555-3001', 'other', '1991-12-03',
 '159 Cedar Street', 'Unit 7', '10006', 'Brooklyn', 'United States',
 '["customer", "employee"]', 'active',
 'Personal Trainer', 'Fitness', '2021-09-01', 'BADGE-55', '$2y$10$encrypted_password_hash',
 'Certified personal trainer specializing in strength training and HIIT workouts.',
 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150', '["service_005"]',
 'Employee discount applied. Books weekly massage sessions.', '[{"key":"employee_discount","value":"20%"}]'),

-- Dual Role 2 - Part-time instructor who is also a customer
(1, 'Rachel', 'White', 'rachel.white@test.bookando.local', '+1 555-3002', 'female', '1994-06-17',
 '753 Willow Way', NULL, '10007', 'Manhattan', 'United States',
 '["customer", "employee"]', 'active',
 'Meditation Instructor', 'Wellness', '2023-05-15', 'BADGE-88', '$2y$10$encrypted_password_hash',
 'Part-time meditation and mindfulness instructor. Teaches weekend retreat workshops.',
 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150', '["service_006"]',
 'Also participates in yoga classes. Interested in nutrition consultations.', '[]');

-- ============================================================================
-- SUMMARY
-- ============================================================================
-- Total inserted:
--   5 Customers only
--   5 Employees only
--   2 Dual roles (customer + employee)
-- Total: 12 users
-- ============================================================================

-- Verify insert
SELECT
  COUNT(*) as total_users,
  SUM(JSON_CONTAINS(roles, '"customer"')) as customers,
  SUM(JSON_CONTAINS(roles, '"employee"')) as employees,
  SUM(JSON_CONTAINS(roles, '"customer"') AND JSON_CONTAINS(roles, '"employee"')) as dual_roles
FROM wp_bookando_users
WHERE email LIKE '%@test.bookando.local';
