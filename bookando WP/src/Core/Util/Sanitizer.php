<?php
namespace Bookando\Core\Util;

final class Sanitizer
{
    public static function text(?string $v): string { return sanitize_text_field((string) $v); }
    public static function textarea(?string $v): string { return sanitize_textarea_field((string) $v); }
    public static function email(?string $v): string { return sanitize_email((string) $v); }
    public static function key(?string $v): string { return sanitize_key((string) $v); }
    public static function positiveInt($v): int { $i = (int)$v; return $i > 0 ? $i : 0; }
    public static function html(string $v): string { return wp_kses_post($v); }
    
    /**
     * Länder-Code auf 2 Großbuchstaben normalisieren (ISO-ALPHA-2), sonst null.
     * Akzeptiert auch Objekte/Arrays mit ->code / ['code'].
     */
    public static function country($val): ?string {
        if ($val === null) return null;

        if (is_array($val) && isset($val['code'])) $val = $val['code'];
        if (is_object($val) && isset($val->code))   $val = $val->code;

        $v = strtoupper(trim((string)$val));
        return preg_match('/^[A-Z]{2}$/', $v) ? $v : null;
    }

    /**
     * Sprache vereinheitlichen. Behalte dein Schema (de_CH).
     * '-' und '_' werden auf '_' normalisiert.
     * Teil 1 (Sprache) lowercase, Teil 2 (Region) uppercase. Leer -> null.
     */
    public static function language($val): ?string {
        if ($val === null) return null;

        if (is_array($val) && isset($val['value'])) $val = $val['value'];
        if (is_object($val) && isset($val->value))   $val = $val->value;

        $v = trim((string)$val);
        if ($v === '') return null;

        $v = str_replace('-', '_', $v);
        $parts = explode('_', $v, 2);
        $lang  = strtolower($parts[0] ?? '');
        $reg   = strtoupper($parts[1] ?? '');

        return $reg !== '' ? ($lang . '_' . $reg) : $lang;
    }

    /**
     * Telefonnummer nur auf Ziffern und '+' reduzieren; leer -> null.
     */
    public static function phone($val): ?string {
        if ($val === null) return null;
        $v = preg_replace('/[^0-9+]+/', '', (string)$val);
        return $v !== '' ? $v : null;
    }

    /**
     * Trimmt und gibt null zurück, wenn leer.
     */
    public static function nullIfEmpty($val): ?string {
        if ($val === null) return null;
        $v = trim((string)$val);
        return $v === '' ? null : $v;
    }

    /** Normiert Zeiten auf HH:mm; invalid -> null. */
    public static function time(?string $t): ?string {
        if ($t === null) return null;
        $t = preg_replace('/[^0-9:]+/', '', (string)$t);
        if ($t === '') return null;
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $t)) return null;
        return substr($t, 0, 5); // HH:mm
    }

    /** Akzeptiert nur YYYY-MM-DD; sonst null. */
    public static function date(?string $d): ?string {
        if ($d === null) return null;
        $d = trim((string)$d);
        if ($d === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }
}
