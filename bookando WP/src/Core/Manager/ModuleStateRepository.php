<?php

namespace Bookando\Core\Manager;

use wpdb;
use Bookando\Core\Service\ActivityLogger;

final class ModuleStateRepository
{
    private const LEGACY_OPTION_KEY = 'bookando_active_modules';
    private const LEGACY_INSTALLED_PREFIX = 'bookando_module_installed_at_';

    private static ?self $instance = null;

    private ?wpdb $wpdb;
    private string $table;
    private bool $tableExists;

    private function __construct(?wpdb $db = null)
    {
        global $wpdb;
        $this->wpdb = $db ?? ($wpdb instanceof wpdb ? $wpdb : null);
        $this->table = $this->wpdb instanceof wpdb ? $this->wpdb->prefix . 'bookando_module_states' : '';
        $this->tableExists = $this->detectTable();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function getActiveSlugs(): array
    {
        if (!$this->hasTable()) {
            return $this->getLegacyActiveSlugs();
        }

        $slugs = $this->wpdb->get_col("SELECT slug FROM {$this->table} WHERE status = 'active'") ?: [];
        $slugs = array_values(array_unique(array_map('strval', $slugs)));
        $this->syncLegacyOption($slugs);
        return $slugs;
    }

    public function isActive(string $slug): bool
    {
        $slug = sanitize_key($slug);
        if (!$this->hasTable()) {
            return in_array($slug, $this->getLegacyActiveSlugs(), true);
        }

        $status = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT status FROM {$this->table} WHERE slug = %s",
            $slug
        ));
        return $status === 'active';
    }

    public function activate(string $slug, ?int $userId = null): bool
    {
        $slug = sanitize_key($slug);
        $userId = $userId ?? get_current_user_id();
        $userId = $userId ? (int) $userId : null;

        if (!$this->hasTable()) {
            $active = $this->getLegacyActiveSlugs();
            if (!in_array($slug, $active, true)) {
                $active[] = $slug;
                update_option(self::LEGACY_OPTION_KEY, $active, false);
            }
            return true;
        }

        $now = current_time('mysql');
        $state = $this->getState($slug);
        if (!$state) {
            $this->wpdb->insert(
                $this->table,
                [
                    'slug'         => $slug,
                    'status'       => 'active',
                    'installed_at' => $now,
                    'activated_at' => $now,
                    'activated_by' => $userId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                ['%s', '%s', '%s', '%s', '%d', '%s', '%s']
            );
        } else {
            $data = [
                'status'       => 'active',
                'updated_at'   => $now,
                'activated_by' => $userId,
            ];
            $formats = ['%s', '%s', '%d'];
            if (empty($state->installed_at)) {
                $data['installed_at'] = $now;
                $formats[] = '%s';
            }
            if ($state->status !== 'active') {
                $data['activated_at']   = $now;
                $formats[] = '%s';
            }
            $this->wpdb->update(
                $this->table,
                $data,
                ['slug' => $slug],
                $formats,
                ['%s']
            );
            if ($state->status !== 'active') {
                $this->wpdb->query(
                    $this->wpdb->prepare(
                        "UPDATE {$this->table} SET deactivated_at = NULL, deactivated_by = NULL WHERE slug = %s",
                        $slug
                    )
                );
            }
        }

        $this->syncLegacyOption($this->getActiveSlugs());
        return true;
    }

    public function deactivate(string $slug, ?int $userId = null): bool
    {
        $slug = sanitize_key($slug);
        $userId = $userId ?? get_current_user_id();
        $userId = $userId ? (int) $userId : null;

        if (!$this->tableExists || !$this->wpdb instanceof wpdb) {
            $active = array_diff($this->getLegacyActiveSlugs(), [$slug]);
            update_option(self::LEGACY_OPTION_KEY, array_values($active), false);
            return true;
        }

        $now = current_time('mysql');
        $this->wpdb->update(
            $this->table,
            [
                'status'         => 'inactive',
                'updated_at'     => $now,
                'deactivated_at' => $now,
                'deactivated_by' => $userId,
            ],
            ['slug' => $slug],
            ['%s', '%s', '%s', '%d'],
            ['%s']
        );

        $this->syncLegacyOption($this->getActiveSlugs());
        return true;
    }

    public function persistInitialState(string $slug, bool $active, ?int $installedAt = null): void
    {
        if (!$this->hasTable()) {
            return;
        }

        $slug = sanitize_key($slug);
        $state = $this->getState($slug);
        $now = current_time('mysql');
        $installed = $installedAt ? get_date_from_gmt(gmdate('Y-m-d H:i:s', $installedAt)) : $now;

        if (!$state) {
            $this->wpdb->insert(
                $this->table,
                [
                    'slug'         => $slug,
                    'status'       => $active ? 'active' : 'inactive',
                    'installed_at' => $installed,
                    'activated_at' => $active ? $now : null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s']
            );
            return;
        }

        $data = ['updated_at' => $now];
        $formats = ['%s'];

        if ($active && $state->status !== 'active') {
            $data['status'] = 'active';
            $data['activated_at'] = $now;
            $formats[] = '%s';
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "UPDATE {$this->table} SET deactivated_at = NULL, deactivated_by = NULL WHERE slug = %s",
                    $slug
                )
            );
        } elseif (!$active && $state->status !== 'inactive') {
            $data['status'] = 'inactive';
            $formats[] = '%s';
        }

        if (empty($state->installed_at)) {
            $data['installed_at'] = $installed;
            $formats[] = '%s';
        }

        if (count($data) > 1) {
            $this->wpdb->update(
                $this->table,
                $data,
                ['slug' => $slug],
                $formats,
                ['%s']
            );
        }
    }

    public function recordInstallation(string $slug, ?int $timestamp = null): void
    {
        if (!$this->hasTable()) {
            return;
        }

        $slug = sanitize_key($slug);
        $state = $this->getState($slug);
        $installed = $timestamp ? get_date_from_gmt(gmdate('Y-m-d H:i:s', $timestamp)) : current_time('mysql');

        if ($state && !empty($state->installed_at)) {
            return;
        }

        if ($state) {
            $this->wpdb->update(
                $this->table,
                ['installed_at' => $installed, 'updated_at' => current_time('mysql')],
                ['slug' => $slug],
                ['%s', '%s'],
                ['%s']
            );
            return;
        }

        $now = current_time('mysql');
        $this->wpdb->insert(
            $this->table,
            [
                'slug'         => $slug,
                'status'       => 'inactive',
                'installed_at' => $installed,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }

    public function getInstalledAt(string $slug): ?int
    {
        $slug = sanitize_key($slug);
        if ($this->hasTable()) {
            $value = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT installed_at FROM {$this->table} WHERE slug = %s",
                $slug
            ));
            if ($value) {
                return strtotime($value);
            }
        }

        $legacy = get_option(self::LEGACY_INSTALLED_PREFIX . strtolower($slug));
        return $legacy ? (int) $legacy : null;
    }

    public function getAllStates(): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        return $this->wpdb->get_results("SELECT * FROM {$this->table}") ?: [];
    }

    public function findInactiveOlderThanDays(int $days): array
    {
        if (!$this->hasTable() || !$this->wpdb instanceof wpdb || $days <= 0) {
            return [];
        }

        $threshold = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        return $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT slug FROM {$this->table} WHERE status = 'inactive' AND updated_at < %s",
            $threshold
        )) ?: [];
    }

    public function hasRecordedState(string $slug): bool
    {
        $slug = sanitize_key($slug);

        if ($this->hasTable() && $this->wpdb instanceof wpdb) {
            $state = $this->getState($slug);
            if ($state) {
                if ($state->status === 'active' || !empty($state->activated_at) || !empty($state->deactivated_at)) {
                    return true;
                }
            }
        }

        if (in_array($slug, $this->getLegacyActiveSlugs(), true)) {
            return true;
        }

        $legacyInstalled = get_option(self::LEGACY_INSTALLED_PREFIX . strtolower($slug), null);
        return $legacyInstalled !== null && $legacyInstalled !== false;
    }

    private function getState(string $slug)
    {
        if (!$this->wpdb instanceof wpdb) {
            return null;
        }

        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE slug = %s",
            $slug
        ));
    }

    private function detectTable(): bool
    {
        if (!$this->wpdb instanceof wpdb || $this->table === '') {
            ActivityLogger::warning('modules.states', 'module_states table not available, falling back to options');
            return false;
        }

        $found = $this->wpdb->get_var($this->wpdb->prepare('SHOW TABLES LIKE %s', $this->table));
        $exists = ($found === $this->table);
        if (!$exists) {
            ActivityLogger::warning('modules.states', 'module_states table not available, falling back to options');
        }
        return $exists;
    }

    private function hasTable(): bool
    {
        if ($this->tableExists && $this->wpdb instanceof wpdb) {
            return true;
        }

        if (!$this->wpdb instanceof wpdb) {
            return false;
        }

        $this->tableExists = $this->detectTable();
        return $this->tableExists;
    }

    private function getLegacyActiveSlugs(): array
    {
        $legacy = get_option(self::LEGACY_OPTION_KEY, []);
        if (!is_array($legacy)) {
            return [];
        }
        return array_values(array_unique(array_map('strval', $legacy)));
    }

    private function syncLegacyOption(array $active): void
    {
        update_option(self::LEGACY_OPTION_KEY, $active, false);
    }
}
