<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Email sending port.
 *
 * Provides a host-agnostic interface for sending emails. Implementations
 * adapt to the host's mail system (e.g. WordPress `wp_mail()`, Symfony
 * Mailer, PHPMailer, an external SMTP/API service, etc.).
 *
 * The `$options` array allows callers to pass additional mail parameters
 * without breaking the interface when new features are needed.
 */
interface MailPort
{
    /**
     * Send an email message.
     *
     * @param string               $to      Recipient email address.
     * @param string               $subject Email subject line.
     * @param string               $body    Email body (HTML or plain text).
     * @param array<string, mixed> $options Optional parameters. Common keys include:
     *                                      - "from"        (string) Sender address override.
     *                                      - "reply_to"    (string) Reply-To address.
     *                                      - "cc"          (string|string[]) CC recipients.
     *                                      - "bcc"         (string|string[]) BCC recipients.
     *                                      - "headers"     (array) Additional headers.
     *                                      - "attachments" (array) File paths or content to attach.
     *                                      - "content_type" (string) "text/html" or "text/plain".
     *
     * @return bool True if the mail was accepted for delivery, false on failure.
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool;
}
