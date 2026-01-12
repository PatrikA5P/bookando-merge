<?php

declare(strict_types=1);

namespace Bookando\Modules\academy;

use function __;
use function add_action;
use function check_admin_referer;
use function current_user_can;
use function delete_option;
use function wp_die;
use function wp_redirect;
use function admin_url;

class AdminResetPage
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addAdminPage'], 100);
        add_action('admin_post_reset_academy_data', [self::class, 'handleReset']);
    }

    public static function addAdminPage(): void
    {
        add_submenu_page(
            null, // Parent slug (null = versteckt im Menü)
            __('Academy Daten zurücksetzen', 'bookando'),
            __('Academy Reset', 'bookando'),
            'manage_options', // Verwende manage_options statt manage_bookando_academy
            'bookando-academy-reset',
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine Berechtigung für diese Aktion.', 'bookando'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Academy Daten zurücksetzen', 'bookando'); ?></h1>

            <div class="notice notice-warning">
                <p><strong><?php echo esc_html__('Achtung!', 'bookando'); ?></strong></p>
                <p><?php echo esc_html__('Dieser Vorgang löscht ALLE Kurse und Ausbildungskarten unwiderruflich.', 'bookando'); ?></p>
                <p><?php echo esc_html__('Die Standard-Beispieldaten werden beim nächsten Laden des Academy-Moduls automatisch neu erstellt.', 'bookando'); ?></p>
            </div>

            <div class="card">
                <h2><?php echo esc_html__('Wann ist dies nützlich?', 'bookando'); ?></h2>
                <ul>
                    <li><?php echo esc_html__('Nach einem Update auf die neue Datenstruktur mit Hauptthemen und Lektionen', 'bookando'); ?></li>
                    <li><?php echo esc_html__('Wenn Kurse oder Ausbildungskarten nicht korrekt angezeigt werden', 'bookando'); ?></li>
                    <li><?php echo esc_html__('Um mit einer frischen Datenbank zu starten', 'bookando'); ?></li>
                </ul>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 20px;">
                <?php wp_nonce_field('reset_academy_data', 'reset_academy_nonce'); ?>
                <input type="hidden" name="action" value="reset_academy_data">

                <p>
                    <button type="submit" class="button button-primary button-large"
                            onclick="return confirm('<?php echo esc_js(__('Sind Sie sicher? Alle Daten werden gelöscht!', 'bookando')); ?>');">
                        <?php echo esc_html__('🔄 Academy-Daten jetzt zurücksetzen', 'bookando'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bookando-academy')); ?>" class="button">
                    <?php echo esc_html__('← Zurück zum Academy-Modul', 'bookando'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    public static function handleReset(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine Berechtigung für diese Aktion.', 'bookando'));
        }

        check_admin_referer('reset_academy_data', 'reset_academy_nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'bookando_academy_';

        error_log('[Bookando Academy Reset] Starting reset...');

        try {
            // Lösche alle Daten aus den Tabellen (CASCADE löscht automatisch verknüpfte Daten)
            $result1 = $wpdb->query("DELETE FROM {$prefix}courses");
            error_log('[Bookando Academy Reset] Deleted ' . $wpdb->rows_affected . ' courses');

            $result2 = $wpdb->query("DELETE FROM {$prefix}training_cards");
            error_log('[Bookando Academy Reset] Deleted ' . $wpdb->rows_affected . ' training cards');

            if ($wpdb->last_error) {
                error_log('[Bookando Academy Reset] Database error: ' . $wpdb->last_error);
                wp_die('Database error: ' . $wpdb->last_error);
            }

            // Lösche alte wp_options Daten
            delete_option('bookando_academy_state');
            delete_option('bookando_academy_migrated');

            error_log('[Bookando Academy Reset] Reset completed successfully');
        } catch (\Exception $e) {
            error_log('[Bookando Academy Reset] Exception: ' . $e->getMessage());
            wp_die('Error during reset: ' . $e->getMessage());
        }

        // Redirect zurück zum Academy-Modul mit Erfolgsmeldung
        wp_redirect(
            add_query_arg(
                [
                    'page' => 'bookando-academy',
                    'academy_reset' => '1'
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
