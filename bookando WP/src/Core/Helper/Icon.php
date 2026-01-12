<?php

namespace Bookando\Core\Helper;

defined('ABSPATH') || exit;

class Icon
{
    /**
     * Gibt ein SVG-Icon-HTML-Tag zurück
     *
     * @param string $name  Dateiname ohne ".svg"
     * @param string $class Zusätzliche CSS-Klassen (z.B. "icon-green")
     * @param string $alt   Alt-Text (Standard leer)
     * @return string       HTML-Tag als <img>
     */
    public static function render(string $name, string $class = '', string $alt = ''): string
    {
        $src = plugins_url("src/Core/Design/assets/icons/{$name}.svg", BOOKANDO_PLUGIN_FILE);
        return sprintf(
            '<img src="%s" class="bookando-icon %s" alt="%s" loading="lazy">',
            esc_url($src),
            esc_attr($class),
            esc_attr($alt)
        );
    }
}
