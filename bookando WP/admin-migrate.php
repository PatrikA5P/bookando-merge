<?php
/**
 * Admin page to run Migration 003
 *
 * Installation:
 * 1. Upload this file to your WordPress root or plugin directory
 * 2. Access via: your-domain.com/wp-admin/admin.php?page=bookando-migrate
 *
 * Or add to your theme's functions.php temporarily:
 * require_once '/path/to/this/file';
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
    require_once ABSPATH . 'wp-load.php';
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Handle migration execution
$migration_result = null;
$migration_error = null;

if (isset($_POST['run_migration']) && check_admin_referer('bookando_migration_003')) {
    try {
        // Load the migration class
        require_once __DIR__ . '/src/Core/Database/Migration003_TimeTrackingAndShiftManagement.php';

        // Run the migration
        $result = \Bookando\Core\Database\Migration003_TimeTrackingAndShiftManagement::up();

        if ($result) {
            $migration_result = 'success';
            update_option('bookando_migration_003_executed', time());
            update_option('bookando_migration_003_version', '1.0.0');
        } else {
            $migration_error = 'Migration returned false. Check database error logs.';
        }
    } catch (Exception $e) {
        $migration_error = $e->getMessage();
    }
}

// Check if migration was already executed
$migration_executed = get_option('bookando_migration_003_executed', false);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bookando Migration 003</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            margin-top: 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .button:hover {
            background: #135e96;
        }
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        ul {
            line-height: 1.8;
        }
        code {
            background: #f6f7f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Bookando Migration 003</h1>
        <p><strong>Time Tracking & Shift Management</strong></p>

        <?php if ($migration_result === 'success'): ?>
            <div class="success">
                <strong>✅ Migration completed successfully!</strong>
                <p>The following database changes have been applied:</p>
                <ul>
                    <li>Created table: <code>time_entry_breaks</code></li>
                    <li>Created table: <code>employee_vacation_balances</code></li>
                    <li>Created table: <code>shifts</code></li>
                    <li>Created table: <code>shift_templates</code></li>
                    <li>Created table: <code>employee_shift_preferences</code></li>
                    <li>Created table: <code>shift_requirements</code></li>
                    <li>Created table: <code>overtime_balances</code></li>
                    <li>Extended table: <code>employees_days_off</code> (added 5 columns)</li>
                </ul>
                <p><strong>Next steps:</strong></p>
                <ol>
                    <li>Clear your WordPress cache (if you use a caching plugin)</li>
                    <li>Clear your browser cache (Ctrl+Shift+R or Cmd+Shift+R)</li>
                    <li>Go to the Workday module and test the new features</li>
                </ol>
            </div>
        <?php elseif ($migration_error): ?>
            <div class="error">
                <strong>❌ Migration failed!</strong>
                <p><strong>Error:</strong> <?php echo esc_html($migration_error); ?></p>
                <p>Please check your database error logs for more details.</p>
            </div>
        <?php endif; ?>

        <?php if ($migration_executed): ?>
            <div class="info">
                <strong>ℹ️ Migration already executed</strong>
                <p>Migration 003 was executed at: <code><?php echo date('Y-m-d H:i:s', $migration_executed); ?></code></p>
                <p>You can re-run it if needed, but this may cause errors if the tables already exist.</p>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Migration not yet executed</strong>
                <p>Click the button below to create the new database tables and extend existing ones.</p>
            </div>
        <?php endif; ?>

        <h2>📋 What will this migration do?</h2>
        <ul>
            <li><strong>Break Tracking:</strong> Track employee breaks with types (paid, unpaid, meal, rest)</li>
            <li><strong>Vacation Balances:</strong> Manage vacation entitlements and track usage</li>
            <li><strong>Shift Management:</strong> Create and manage employee shifts with status workflow</li>
            <li><strong>Shift Templates:</strong> Reusable shift patterns for scheduling</li>
            <li><strong>Overtime Tracking:</strong> Track overtime hours and balances</li>
            <li><strong>Extended Absences:</strong> Enhanced absence tracking with types and certificates</li>
        </ul>

        <form method="post" style="margin-top: 30px;">
            <?php wp_nonce_field('bookando_migration_003'); ?>
            <button type="submit" name="run_migration" class="button">
                <?php echo $migration_executed ? '🔄 Re-run Migration 003' : '▶️ Run Migration 003'; ?>
            </button>
        </form>

        <p style="margin-top: 30px; color: #666; font-size: 13px;">
            <strong>Note:</strong> This page can be deleted after the migration is complete.
        </p>
    </div>
</body>
</html>
