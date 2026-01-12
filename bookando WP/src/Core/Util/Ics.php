<?php
namespace Bookando\Core\Util;

final class Ics
{
    /**
     * Baut ein einfaches VCALENDAR/VEVENT.
     * Erwartet ISO-8601 (mit Offset) fuer start/end.
     */
    public static function buildEvent(array $e): string
    {
        $uid   = $e['uid'] ?: self::uuid();
        $sum   = self::esc($e['summary'] ?? '');
        $desc  = self::esc($e['description'] ?? '');
        $loc   = self::esc($e['location'] ?? '');
        $org   = sanitize_email($e['organizer'] ?? '');
        $atts  = array_values(array_filter((array)($e['attendees'] ?? []), 'is_email'));

        $dtStart = self::toUtc($e['start'] ?? '');
        $dtEnd   = self::toUtc($e['end']   ?? '');

        $lines = [
            'BEGIN:VCALENDAR',
            'PRODID:-//Bookando//Calendar//DE',
            'VERSION:2.0',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            $dtStart ? 'DTSTART:' . $dtStart : '',
            $dtEnd   ? 'DTEND:'   . $dtEnd   : '',
            $sum !== ''  ? 'SUMMARY:' . $sum : '',
            $desc !== '' ? 'DESCRIPTION:' . $desc : '',
            $loc  !== '' ? 'LOCATION:' . $loc : '',
            $org  !== '' ? 'ORGANIZER:MAILTO:' . $org : '',
        ];
        foreach ($atts as $a) {
            $lines[] = 'ATTENDEE;CN=' . self::esc($a) . ';RSVP=TRUE:MAILTO:' . $a;
        }
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        // Falten laut RFC 5545 (75 Oktetten)
        $ics = implode("\r\n", array_filter($lines, fn($l)=>$l!==''));
        return preg_replace('/(.{1,72})(?=.)/u', "$1\r\n ", $ics);
    }

    /**
     * Versendet die Einladung mit Anhang .ics (multipart/alternative + text/calendar).
     */
    public static function sendInvite(array $to, string $subject, string $body, string $ics): bool
    {
        $upload = wp_upload_dir();
        $file   = $upload['basedir'] . '/bookando-invite-' . time() . '-' . wp_generate_password(6,false) . '.ics';
        file_put_contents($file, $ics);

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $sent = wp_mail($to, $subject, $body ?: 'Kalendereinladung im Anhang.', $headers, [$file]);

        // Aufräumen
        if (file_exists($file)) @unlink($file);
        return (bool)$sent;
    }

    /* ----------------------- Helpers ----------------------- */

    private static function esc(string $v): string
    {
        $v = str_replace(["\\", "\n", "\r", "," , ";"], ["\\\\", "\\n", "", "\,", "\;"], $v);
        return $v;
    }

    private static function toUtc(string $iso): string
    {
        if ($iso === '') return '';
        try {
            $dt = new \DateTime($iso);
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Ymd\THis\Z');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function uuid(): string
    {
        return wp_generate_uuid4();
    }
}
