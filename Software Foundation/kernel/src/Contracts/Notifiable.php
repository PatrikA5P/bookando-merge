<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Contracts;

/**
 * Contract that modules implement to declare their notification events.
 *
 * Modules that can trigger notifications (booking confirmed, invoice sent, etc.)
 * implement this interface to register their event types and default templates
 * with the notification system.
 */
interface Notifiable
{
    /**
     * List of notification event types this module can trigger.
     *
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     default_channels: string[],
     *     template_vars: string[]
     * }>
     *
     * Example:
     * [
     *     'booking.confirmed' => [
     *         'label' => 'Booking Confirmed',
     *         'description' => 'Sent when a booking is confirmed',
     *         'default_channels' => ['email', 'push'],
     *         'template_vars' => ['customer_name', 'booking_date', 'service_name'],
     *     ],
     * ]
     */
    public function notificationEvents(): array;

    /**
     * Provide the default notification templates for this module.
     *
     * @param string $locale BCP-47 locale tag (e.g. 'de-CH').
     *
     * @return array<string, array{subject: string, body: string}>
     *     Keyed by event type.
     */
    public function defaultTemplates(string $locale): array;
}
