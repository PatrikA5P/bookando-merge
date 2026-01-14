<?php

namespace Bookando\Modules\Partnerhub\Services;

use Bookando\Modules\Partnerhub\Models\PartnerFeedModel;
use Bookando\Modules\Partnerhub\Models\PartnerMappingModel;
use Bookando\Modules\Partnerhub\Models\PartnerRuleModel;
use Bookando\Modules\Partnerhub\Models\PartnerAuditLogModel;

/**
 * Feed Service
 *
 * Export von Dienstleistungen und Kursen für Partner
 */
class FeedService
{
    private PartnerFeedModel $feed_model;
    private PartnerMappingModel $mapping_model;
    private PartnerRuleModel $rule_model;
    private PartnerAuditLogModel $audit_model;

    public function __construct()
    {
        $this->feed_model = new PartnerFeedModel();
        $this->mapping_model = new PartnerMappingModel();
        $this->rule_model = new PartnerRuleModel();
        $this->audit_model = new PartnerAuditLogModel();
    }

    /**
     * Generate feed for partner
     */
    public function generate_feed(int $feed_id, string $ip_address): array
    {
        $feed = $this->feed_model->get_by_id($feed_id);

        if (!$feed || $feed->status !== 'active') {
            throw new \Exception('Feed nicht gefunden oder inaktiv');
        }

        // Check IP whitelist
        if ($feed->ip_whitelist && !$this->is_ip_allowed($ip_address, $feed->ip_whitelist)) {
            throw new \Exception('IP-Adresse nicht autorisiert');
        }

        // Check rate limit
        if (!$this->feed_model->check_rate_limit($feed_id)) {
            throw new \Exception('Rate limit überschritten');
        }

        // Record access
        $this->feed_model->record_access($feed_id, $ip_address);
        $this->audit_model->log_feed_accessed($feed_id, $feed->partner_id ?? 0, $ip_address);

        // Get items based on feed configuration
        $items = $this->get_feed_items($feed);

        // Apply partner-specific rules
        if ($feed->partner_id) {
            $items = $this->apply_partner_rules($items, $feed->partner_id);
        }

        // Format based on feed type
        return $this->format_feed($items, $feed->feed_type);
    }

    /**
     * Get items for feed
     */
    private function get_feed_items(object $feed): array
    {
        global $wpdb;
        $items = [];

        $include_types = json_decode($feed->include_types, true) ?: ['service', 'event'];

        // Get from offers table (simplified - would integrate with actual offers module)
        foreach ($include_types as $type) {
            $table = $wpdb->prefix . 'bookando_offers';

            $sql = "SELECT * FROM {$table}
                    WHERE tenant_id = %d
                    AND type = %s
                    AND status = 'active'
                    AND deleted_at IS NULL";

            $params = [$feed->tenant_id, $type];

            // Apply category filters
            if ($feed->include_categories) {
                $categories = json_decode($feed->include_categories, true);
                $placeholders = implode(',', array_fill(0, count($categories), '%d'));
                $sql .= " AND category_id IN ({$placeholders})";
                $params = array_merge($params, $categories);
            }

            // Apply location filters
            if ($feed->include_locations) {
                $locations = json_decode($feed->include_locations, true);
                $placeholders = implode(',', array_fill(0, count($locations), '%d'));
                $sql .= " AND location_id IN ({$placeholders})";
                $params = array_merge($params, $locations);
            }

            $results = $wpdb->get_results($wpdb->prepare($sql, $params));

            if ($results) {
                $items = array_merge($items, $results);
            }
        }

        return $items;
    }

    /**
     * Apply partner-specific rules (pricing, availability)
     */
    private function apply_partner_rules(array $items, int $partner_id): array
    {
        foreach ($items as &$item) {
            // Check if mapping exists
            $mapping = $this->mapping_model->get_by_local_item($item->type, $item->id, $partner_id);

            $mapping_id = $mapping ? $mapping->id : null;

            // Apply pricing rules
            if (isset($item->price)) {
                $item->original_price = $item->price;
                $item->price = $this->rule_model->apply_pricing_rules($item->price, $partner_id, $mapping_id);
            }

            // Apply availability rules
            $availability = $this->rule_model->check_availability($partner_id, $mapping_id);
            $item->available = $availability['available'];
            $item->requires_approval = $availability['requires_approval'];

            // Apply mapping overrides
            if ($mapping) {
                if ($mapping->override_title) {
                    $item->title = $mapping->override_title;
                }
                if ($mapping->override_description) {
                    $item->description = $mapping->override_description;
                }
                if ($mapping->override_price) {
                    $item->price = $mapping->override_price;
                }
            }
        }

        return $items;
    }

    /**
     * Format feed output
     */
    private function format_feed(array $items, string $format): array
    {
        switch ($format) {
            case 'json':
                return $this->format_json($items);

            case 'xml':
                return ['xml' => $this->format_xml($items)];

            case 'ical':
                return ['ical' => $this->format_ical($items)];

            case 'csv':
                return ['csv' => $this->format_csv($items)];

            default:
                return $this->format_json($items);
        }
    }

    /**
     * Format as JSON
     */
    private function format_json(array $items): array
    {
        return [
            'version' => '1.0',
            'generated_at' => current_time('c'),
            'count' => count($items),
            'items' => array_map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title ?? '',
                    'description' => $item->description ?? '',
                    'price' => $item->price ?? 0,
                    'currency' => $item->currency ?? 'EUR',
                    'available' => $item->available ?? true,
                    'requires_approval' => $item->requires_approval ?? false,
                    'url' => get_permalink($item->id),
                    'image' => $item->image_url ?? null,
                ];
            }, $items),
        ];
    }

    /**
     * Format as XML
     */
    private function format_xml(array $items): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><feed/>');
        $xml->addAttribute('version', '1.0');
        $xml->addChild('generated_at', current_time('c'));
        $xml->addChild('count', count($items));

        $items_node = $xml->addChild('items');

        foreach ($items as $item) {
            $item_node = $items_node->addChild('item');
            $item_node->addChild('id', $item->id);
            $item_node->addChild('type', $item->type);
            $item_node->addChild('title', htmlspecialchars($item->title ?? ''));
            $item_node->addChild('description', htmlspecialchars($item->description ?? ''));
            $item_node->addChild('price', $item->price ?? 0);
            $item_node->addChild('currency', $item->currency ?? 'EUR');
            $item_node->addChild('available', $item->available ?? true ? 'true' : 'false');
        }

        return $xml->asXML();
    }

    /**
     * Format as iCal (for events)
     */
    private function format_ical(array $items): string
    {
        $output = "BEGIN:VCALENDAR\r\n";
        $output .= "VERSION:2.0\r\n";
        $output .= "PRODID:-//Bookando//Partnerhub//EN\r\n";

        foreach ($items as $item) {
            if ($item->type !== 'event') {
                continue;
            }

            $output .= "BEGIN:VEVENT\r\n";
            $output .= "UID:" . $item->id . "@bookando\r\n";
            $output .= "SUMMARY:" . $this->escape_ical($item->title ?? '') . "\r\n";
            $output .= "DESCRIPTION:" . $this->escape_ical($item->description ?? '') . "\r\n";

            if (!empty($item->start_date)) {
                $output .= "DTSTART:" . date('Ymd\THis\Z', strtotime($item->start_date)) . "\r\n";
            }

            if (!empty($item->end_date)) {
                $output .= "DTEND:" . date('Ymd\THis\Z', strtotime($item->end_date)) . "\r\n";
            }

            $output .= "END:VEVENT\r\n";
        }

        $output .= "END:VCALENDAR\r\n";

        return $output;
    }

    /**
     * Format as CSV
     */
    private function format_csv(array $items): string
    {
        $output = "ID,Type,Title,Description,Price,Currency,Available\n";

        foreach ($items as $item) {
            $output .= sprintf(
                "%d,%s,%s,%s,%.2f,%s,%s\n",
                $item->id,
                $item->type,
                $this->escape_csv($item->title ?? ''),
                $this->escape_csv($item->description ?? ''),
                $item->price ?? 0,
                $item->currency ?? 'EUR',
                ($item->available ?? true) ? 'yes' : 'no'
            );
        }

        return $output;
    }

    /**
     * Check if IP is allowed
     */
    private function is_ip_allowed(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowed) {
            if ($ip === $allowed || fnmatch($allowed, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Escape iCal string
     */
    private function escape_ical(string $str): string
    {
        return str_replace(["\r\n", "\n", "\r", ",", ";"], ["\\n", "\\n", "\\n", "\\,", "\\;"], $str);
    }

    /**
     * Escape CSV string
     */
    private function escape_csv(string $str): string
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }
}
