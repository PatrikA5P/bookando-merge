<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend\Admin;

/**
 * Shortcode Generator Admin Page
 *
 * Visual interface to generate shortcodes with all variations
 */
class ShortcodeGeneratorPage
{
    public static function render(): void
    {
        ?>
        <div class="wrap">
            <h1>📋 Shortcode & Link Generator</h1>
            <p class="description">
                Erstellen Sie Shortcodes mit ID-basierten Filtern (Amelia-Style) oder nutzen Sie direkte SaaS-Links.
            </p>

            <div class="bookando-shortcode-generator" style="margin-top: 30px;">

                <!-- Tab Navigation -->
                <h2 class="nav-tab-wrapper">
                    <a href="#booking" class="nav-tab nav-tab-active" data-tab="booking">🎯 Step-by-Step Booking</a>
                    <a href="#catalog" class="nav-tab" data-tab="catalog">📦 Catalog View</a>
                    <a href="#list" class="nav-tab" data-tab="list">📋 List View</a>
                    <a href="#calendar" class="nav-tab" data-tab="calendar">📅 Calendar View</a>
                    <a href="#customer-portal" class="nav-tab" data-tab="customer-portal">👤 Kundenportal</a>
                    <a href="#employee-portal" class="nav-tab" data-tab="employee-portal">👔 Mitarbeiterportal</a>
                </h2>

                <!-- Booking Tab (Step-by-Step Wizard) -->
                <div id="booking" class="tab-content active" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                    <h2>🎯 Step-by-Step Booking (Buchungs-Wizard)</h2>
                    <p>Schrittweiser Buchungsprozess mit Auswahl von Service, Mitarbeiter, Datum und Zeit.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Angebots-IDs</label></th>
                            <td>
                                <input type="text" id="booking-offer" class="regular-text" placeholder="z.B. 1,2,3">
                                <p class="description">Optional: Komma-getrennte IDs (leer = alle)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Kategorie-IDs</label></th>
                            <td>
                                <input type="text" id="booking-category" class="regular-text" placeholder="z.B. 1,2">
                                <p class="description">Optional: Filter nach Kategorie-IDs</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Tag-IDs</label></th>
                            <td>
                                <input type="text" id="booking-tag" class="regular-text" placeholder="z.B. {1,2,3}">
                                <p class="description">Optional: Tags in geschweiften Klammern</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Mitarbeiter-IDs</label></th>
                            <td>
                                <input type="text" id="booking-employee" class="regular-text" placeholder="z.B. 5 oder 1,2,3">
                                <p class="description">Optional: Nur bestimmte Mitarbeiter</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Standort-IDs</label></th>
                            <td>
                                <input type="text" id="booking-location" class="regular-text" placeholder="z.B. 1,2">
                                <p class="description">Optional: Filter nach Standorten</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Paket-IDs</label></th>
                            <td>
                                <input type="text" id="booking-package" class="regular-text" placeholder="z.B. 1,2,3">
                                <p class="description">Optional: Nur bestimmte Pakete</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Anzeigen</label></th>
                            <td>
                                <select id="booking-show">
                                    <option value="all">Alles</option>
                                    <option value="courses">Nur Kurse</option>
                                    <option value="packages">Nur Pakete</option>
                                    <option value="appointments">Nur Termine</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Layout</label></th>
                            <td>
                                <select id="booking-layout">
                                    <option value="wizard">Wizard (Schritt-für-Schritt)</option>
                                    <option value="compact">Kompakt</option>
                                    <option value="inline">Inline</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Theme-ID</label></th>
                            <td>
                                <input type="number" id="booking-theme" class="small-text" placeholder="z.B. 1">
                                <p class="description">Optional: Design-Theme ID</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Als Popup</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="booking-in-dialog">
                                    Im Popup-Dialog anzeigen
                                </label>
                            </td>
                        </tr>
                        <tr id="booking-trigger-row" style="display: none;">
                            <th><label>Popup-Trigger</label></th>
                            <td>
                                <input type="text" id="booking-trigger" class="regular-text" placeholder="z.B. #book-now oder .btn-book">
                                <select id="booking-trigger-type" style="margin-left: 10px;">
                                    <option value="id">ID (#)</option>
                                    <option value="class">Class (.)</option>
                                </select>
                                <p class="description">Element, das Popup öffnet</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>URL-Vorauswahl</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="booking-preselect" checked>
                                    URL-Parameter erlauben (z.B. ?offer=123)
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="booking-shortcode" style="display: block; word-break: break-all;">[bookando_booking]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('booking')">
                            📋 Kopieren
                        </button>
                    </div>

                    <h3 style="margin-top: 30px;">💡 Beispiele:</h3>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Verwendung</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Alle Angebote</td>
                                <td><code>[bookando_booking]</code></td>
                            </tr>
                            <tr>
                                <td>Kategorien 1 und 2</td>
                                <td><code>[bookando_booking category=1,2]</code></td>
                            </tr>
                            <tr>
                                <td>Mit Mitarbeiter-Filter</td>
                                <td><code>[bookando_booking category=1,2 employee=5]</code></td>
                            </tr>
                            <tr>
                                <td>Bestimmtes Angebot</td>
                                <td><code>[bookando_booking offer=123 show=details]</code></td>
                            </tr>
                            <tr>
                                <td>Mit Popup-Trigger</td>
                                <td><code>[bookando_booking trigger="#book-now" trigger_type="id" in_dialog=1]</code></td>
                            </tr>
                            <tr>
                                <td>Tags in geschweiften Klammern</td>
                                <td><code>[bookando_booking tag={1,2,3} category=1]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Catalog Tab -->
                <div id="catalog" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>📦 Catalog View (Katalog-Ansicht)</h2>
                    <p>Angebote als Katalog mit Karten, ähnlich wie Amelia Catalog.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Kategorie-IDs</label></th>
                            <td>
                                <input type="text" id="catalog-category" class="regular-text" placeholder="z.B. 1,2,3">
                                <p class="description">Optional: Komma-getrennte IDs</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Tag-IDs</label></th>
                            <td>
                                <input type="text" id="catalog-tag" class="regular-text" placeholder="z.B. {1,2}">
                                <p class="description">Optional: Tags in geschweiften Klammern</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Mitarbeiter-IDs</label></th>
                            <td>
                                <input type="text" id="catalog-employee" class="regular-text" placeholder="z.B. 5">
                                <p class="description">Optional: Filter nach Instruktor</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Standort-IDs</label></th>
                            <td>
                                <input type="text" id="catalog-location" class="regular-text" placeholder="z.B. 1">
                                <p class="description">Optional: Filter nach Standort</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Layout</label></th>
                            <td>
                                <select id="catalog-layout">
                                    <option value="grid">Grid (Karten)</option>
                                    <option value="list">Liste</option>
                                    <option value="masonry">Masonry</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Spalten</label></th>
                            <td>
                                <select id="catalog-columns">
                                    <option value="2">2 Spalten</option>
                                    <option value="3" selected>3 Spalten</option>
                                    <option value="4">4 Spalten</option>
                                    <option value="5">5 Spalten</option>
                                </select>
                                <p class="description">Nur bei Grid-Layout</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Anzeigen</label></th>
                            <td>
                                <select id="catalog-show">
                                    <option value="all">Alles</option>
                                    <option value="courses">Nur Kurse</option>
                                    <option value="packages">Nur Pakete</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Nur Featured</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="catalog-featured">
                                    Nur hervorgehobene Angebote
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Limit</label></th>
                            <td>
                                <input type="number" id="catalog-limit" value="12" min="1" max="100" style="width: 80px;">
                                <p class="description">Maximale Anzahl</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Sortierung</label></th>
                            <td>
                                <select id="catalog-sort">
                                    <option value="newest">Neueste zuerst</option>
                                    <option value="popular">Beliebteste</option>
                                    <option value="price_asc">Preis aufsteigend</option>
                                    <option value="price_desc">Preis absteigend</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Theme-ID</label></th>
                            <td>
                                <input type="number" id="catalog-theme" class="small-text" placeholder="z.B. 1">
                                <p class="description">Optional: Design-Theme ID</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Pagination</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="catalog-pagination" checked>
                                    Seitennummerierung anzeigen
                                </label>
                                <input type="number" id="catalog-per-page" value="12" min="1" style="width: 80px; margin-left: 10px;">
                                <span style="margin-left: 5px;">Items pro Seite</span>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="catalog-shortcode" style="display: block; word-break: break-all;">[bookando_catalog]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('catalog')">
                            📋 Kopieren
                        </button>
                    </div>

                    <h3 style="margin-top: 30px;">💡 Beispiele:</h3>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Verwendung</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Alle Angebote</td>
                                <td><code>[bookando_catalog]</code></td>
                            </tr>
                            <tr>
                                <td>Kategorien 1 & 2, Grid 3-spaltig</td>
                                <td><code>[bookando_catalog category=1,2 layout=grid columns=3]</code></td>
                            </tr>
                            <tr>
                                <td>Tags mit geschweiften Klammern</td>
                                <td><code>[bookando_catalog tag={1,2} featured=1]</code></td>
                            </tr>
                            <tr>
                                <td>Nur Kurse, Mitarbeiter 5</td>
                                <td><code>[bookando_catalog show=courses employee=5]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- List Tab -->
                <div id="list" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>📋 List View (Listen-Ansicht)</h2>
                    <p>Einfache vertikale Liste der Angebote.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Kategorie-IDs</label></th>
                            <td>
                                <input type="text" id="list-category" class="regular-text" placeholder="z.B. 1,2">
                                <p class="description">Optional: Komma-getrennte IDs</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Tag-IDs</label></th>
                            <td>
                                <input type="text" id="list-tag" class="regular-text" placeholder="z.B. {1,2,3}">
                                <p class="description">Optional: Tags in geschweiften Klammern</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Mitarbeiter-IDs</label></th>
                            <td>
                                <input type="text" id="list-employee" class="regular-text" placeholder="z.B. 2">
                                <p class="description">Optional: Filter nach Instruktor</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Standort-IDs</label></th>
                            <td>
                                <input type="text" id="list-location" class="regular-text" placeholder="z.B. 1">
                                <p class="description">Optional: Filter nach Standort</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Anzeigen</label></th>
                            <td>
                                <select id="list-show">
                                    <option value="all">Alles</option>
                                    <option value="courses">Nur Kurse</option>
                                    <option value="packages">Nur Pakete</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Limit</label></th>
                            <td>
                                <input type="number" id="list-limit" value="10" min="1" max="100" style="width: 80px;">
                                <p class="description">Maximale Anzahl</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Theme-ID</label></th>
                            <td>
                                <input type="number" id="list-theme" class="small-text" placeholder="z.B. 1">
                                <p class="description">Optional: Design-Theme ID</p>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="list-shortcode" style="display: block; word-break: break-all;">[bookando_list]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('list')">
                            📋 Kopieren
                        </button>
                    </div>

                    <h3 style="margin-top: 30px;">💡 Beispiele:</h3>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Verwendung</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Alle Angebote</td>
                                <td><code>[bookando_list]</code></td>
                            </tr>
                            <tr>
                                <td>Kategorie 1, Max 5 Items</td>
                                <td><code>[bookando_list category=1 limit=5]</code></td>
                            </tr>
                            <tr>
                                <td>Nur Kurse, Mitarbeiter 2</td>
                                <td><code>[bookando_list employee=2 show=courses]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Calendar Tab -->
                <div id="calendar" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>📅 Calendar View (Kalender-Ansicht)</h2>
                    <p>Angebote und Termine im Kalender-Format.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Kategorie-IDs</label></th>
                            <td>
                                <input type="text" id="calendar-category" class="regular-text" placeholder="z.B. 1,2">
                                <p class="description">Optional: Komma-getrennte IDs</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Mitarbeiter-IDs</label></th>
                            <td>
                                <input type="text" id="calendar-employee" class="regular-text" placeholder="z.B. 5">
                                <p class="description">Optional: Filter nach Mitarbeiter</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Standort-IDs</label></th>
                            <td>
                                <input type="text" id="calendar-location" class="regular-text" placeholder="z.B. 1">
                                <p class="description">Optional: Filter nach Standort</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Ansicht</label></th>
                            <td>
                                <select id="calendar-view">
                                    <option value="month">Monat</option>
                                    <option value="week">Woche</option>
                                    <option value="day">Tag</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Theme-ID</label></th>
                            <td>
                                <input type="number" id="calendar-theme" class="small-text" placeholder="z.B. 1">
                                <p class="description">Optional: Design-Theme ID</p>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="calendar-shortcode" style="display: block; word-break: break-all;">[bookando_calendar]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('calendar')">
                            📋 Kopieren
                        </button>
                    </div>

                    <h3 style="margin-top: 30px;">💡 Beispiele:</h3>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Verwendung</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Alle Termine</td>
                                <td><code>[bookando_calendar]</code></td>
                            </tr>
                            <tr>
                                <td>Mitarbeiter 5, Kategorie 1</td>
                                <td><code>[bookando_calendar employee=5 category=1]</code></td>
                            </tr>
                            <tr>
                                <td>Wochenansicht</td>
                                <td><code>[bookando_calendar view=week]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Customer Portal Tab -->
                <div id="customer-portal" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>👤 Kundenportal</h2>
                    <p>Geben Sie Ihren Kunden Zugang zu ihrem persönlichen Portal.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Theme-ID</label></th>
                            <td>
                                <input type="number" id="customer-theme" class="small-text" placeholder="z.B. 1">
                                <p class="description">Optional: Design-Theme ID</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Redirect nach Login</label></th>
                            <td>
                                <input type="text" id="customer-redirect" class="regular-text" placeholder="z.B. /meine-buchungen">
                                <p class="description">Optional: URL nach erfolgreichem Login</p>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="customer-shortcode" style="display: block; word-break: break-all;">[bookando_customer_portal]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('customer')">
                            📋 Kopieren
                        </button>
                    </div>

                    <h3 style="margin-top: 30px;">🔗 Alternativer SaaS-Link:</h3>
                    <div style="background: #e7f5fe; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1;">
                        <p style="margin: 0;">Ohne Shortcode direkt verlinken:</p>
                        <code style="display: block; margin-top: 10px; font-size: 14px;"><?php echo home_url('/bookando/portal/customer'); ?></code>
                    </div>

                    <h3 style="margin-top: 30px;">💡 Beispiele:</h3>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Verwendung</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Standard</td>
                                <td><code>[bookando_customer_portal]</code></td>
                            </tr>
                            <tr>
                                <td>Mit Theme</td>
                                <td><code>[bookando_customer_portal theme=1]</code></td>
                            </tr>
                            <tr>
                                <td>Mit Redirect</td>
                                <td><code>[bookando_customer_portal redirect_after_login="/dashboard"]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Employee Portal Tab -->
                <div id="employee-portal" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>👔 Mitarbeiterportal</h2>
                    <p>Portal für Fahrlehrer und Mitarbeiter.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Theme-ID</label></th>
                            <td>
                                <input type="number" id="employee-theme" class="small-text" placeholder="z.B. 1">
                                <p class="description">Optional: Design-Theme ID</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Redirect nach Login</label></th>
                            <td>
                                <input type="text" id="employee-redirect" class="regular-text" placeholder="z.B. /mein-terminplan">
                                <p class="description">Optional: URL nach erfolgreichem Login</p>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="employee-shortcode" style="display: block; word-break: break-all;">[bookando_employee_portal]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('employee')">
                            📋 Kopieren
                        </button>
                    </div>

                    <h3 style="margin-top: 30px;">🔗 Alternativer SaaS-Link:</h3>
                    <div style="background: #e7f5fe; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1;">
                        <p style="margin: 0;">Ohne Shortcode direkt verlinken:</p>
                        <code style="display: block; margin-top: 10px; font-size: 14px;"><?php echo home_url('/bookando/portal/employee'); ?></code>
                    </div>

                    <h3 style="margin-top: 30px;">💡 Beispiele:</h3>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Verwendung</th>
                                <th>Shortcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Standard</td>
                                <td><code>[bookando_employee_portal]</code></td>
                            </tr>
                            <tr>
                                <td>Mit Theme</td>
                                <td><code>[bookando_employee_portal theme=1]</code></td>
                            </tr>
                            <tr>
                                <td>Mit Redirect</td>
                                <td><code>[bookando_employee_portal redirect_after_login="/schedule"]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <style>
            .bookando-shortcode-generator .nav-tab {
                cursor: pointer;
            }
            .bookando-shortcode-generator .tab-content {
                margin-top: 0;
            }
            .bookando-shortcode-generator code {
                background: transparent;
                padding: 0;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab Switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');

                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.tab-content').hide();
                $('#' + tab).show();
            });

            // === BOOKING (Step-by-Step Wizard) ===
            function updateBookingShortcode() {
                let shortcode = '[bookando_booking';
                const offer = $('#booking-offer').val();
                const category = $('#booking-category').val();
                const tag = $('#booking-tag').val();
                const employee = $('#booking-employee').val();
                const location = $('#booking-location').val();
                const packageIds = $('#booking-package').val();
                const show = $('#booking-show').val();
                const layout = $('#booking-layout').val();
                const theme = $('#booking-theme').val();
                const inDialog = $('#booking-in-dialog').is(':checked');
                const trigger = $('#booking-trigger').val();
                const triggerType = $('#booking-trigger-type').val();
                const preselect = $('#booking-preselect').is(':checked');

                if (offer) shortcode += ' offer=' + offer;
                if (category) shortcode += ' category=' + category;
                if (tag) shortcode += ' tag=' + tag;
                if (employee) shortcode += ' employee=' + employee;
                if (location) shortcode += ' location=' + location;
                if (packageIds) shortcode += ' package=' + packageIds;
                if (show !== 'all') shortcode += ' show=' + show;
                if (layout !== 'wizard') shortcode += ' layout=' + layout;
                if (theme) shortcode += ' theme=' + theme;
                if (inDialog) {
                    shortcode += ' in_dialog=1';
                    if (trigger) {
                        shortcode += ' trigger="' + trigger + '"';
                        shortcode += ' trigger_type=' + triggerType;
                    }
                }
                if (!preselect) shortcode += ' preselect=0';

                shortcode += ']';
                $('#booking-shortcode').text(shortcode);
            }

            // Show/hide trigger field based on dialog checkbox
            $('#booking-in-dialog').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#booking-trigger-row').show();
                } else {
                    $('#booking-trigger-row').hide();
                }
                updateBookingShortcode();
            });

            $('#booking-offer, #booking-category, #booking-tag, #booking-employee, #booking-location, #booking-package, #booking-show, #booking-layout, #booking-theme, #booking-trigger, #booking-trigger-type').on('input change', updateBookingShortcode);
            $('#booking-preselect').on('change', updateBookingShortcode);

            // === CATALOG VIEW ===
            function updateCatalogShortcode() {
                let shortcode = '[bookando_catalog';
                const category = $('#catalog-category').val();
                const tag = $('#catalog-tag').val();
                const employee = $('#catalog-employee').val();
                const location = $('#catalog-location').val();
                const layout = $('#catalog-layout').val();
                const columns = $('#catalog-columns').val();
                const show = $('#catalog-show').val();
                const featured = $('#catalog-featured').is(':checked');
                const limit = $('#catalog-limit').val();
                const sort = $('#catalog-sort').val();
                const theme = $('#catalog-theme').val();
                const pagination = $('#catalog-pagination').is(':checked');
                const perPage = $('#catalog-per-page').val();

                if (category) shortcode += ' category=' + category;
                if (tag) shortcode += ' tag=' + tag;
                if (employee) shortcode += ' employee=' + employee;
                if (location) shortcode += ' location=' + location;
                if (layout !== 'grid') shortcode += ' layout=' + layout;
                if (columns !== '3') shortcode += ' columns=' + columns;
                if (show !== 'all') shortcode += ' show=' + show;
                if (featured) shortcode += ' featured=1';
                if (limit !== '12') shortcode += ' limit=' + limit;
                if (sort !== 'newest') shortcode += ' sort=' + sort;
                if (theme) shortcode += ' theme=' + theme;
                if (!pagination) shortcode += ' pagination=0';
                if (perPage !== '12') shortcode += ' per_page=' + perPage;

                shortcode += ']';
                $('#catalog-shortcode').text(shortcode);
            }

            $('#catalog-category, #catalog-tag, #catalog-employee, #catalog-location, #catalog-layout, #catalog-columns, #catalog-show, #catalog-limit, #catalog-sort, #catalog-theme, #catalog-per-page').on('input change', updateCatalogShortcode);
            $('#catalog-featured, #catalog-pagination').on('change', updateCatalogShortcode);

            // === LIST VIEW ===
            function updateListShortcode() {
                let shortcode = '[bookando_list';
                const category = $('#list-category').val();
                const tag = $('#list-tag').val();
                const employee = $('#list-employee').val();
                const location = $('#list-location').val();
                const show = $('#list-show').val();
                const limit = $('#list-limit').val();
                const theme = $('#list-theme').val();

                if (category) shortcode += ' category=' + category;
                if (tag) shortcode += ' tag=' + tag;
                if (employee) shortcode += ' employee=' + employee;
                if (location) shortcode += ' location=' + location;
                if (show !== 'all') shortcode += ' show=' + show;
                if (limit !== '10') shortcode += ' limit=' + limit;
                if (theme) shortcode += ' theme=' + theme;

                shortcode += ']';
                $('#list-shortcode').text(shortcode);
            }

            $('#list-category, #list-tag, #list-employee, #list-location, #list-show, #list-limit, #list-theme').on('input change', updateListShortcode);

            // === CALENDAR VIEW ===
            function updateCalendarShortcode() {
                let shortcode = '[bookando_calendar';
                const category = $('#calendar-category').val();
                const employee = $('#calendar-employee').val();
                const location = $('#calendar-location').val();
                const view = $('#calendar-view').val();
                const theme = $('#calendar-theme').val();

                if (category) shortcode += ' category=' + category;
                if (employee) shortcode += ' employee=' + employee;
                if (location) shortcode += ' location=' + location;
                if (view !== 'month') shortcode += ' view=' + view;
                if (theme) shortcode += ' theme=' + theme;

                shortcode += ']';
                $('#calendar-shortcode').text(shortcode);
            }

            $('#calendar-category, #calendar-employee, #calendar-location, #calendar-view, #calendar-theme').on('input change', updateCalendarShortcode);

            // === CUSTOMER PORTAL ===
            function updateCustomerShortcode() {
                let shortcode = '[bookando_customer_portal';
                const theme = $('#customer-theme').val();
                const redirect = $('#customer-redirect').val();

                if (theme) shortcode += ' theme=' + theme;
                if (redirect) shortcode += ' redirect_after_login="' + redirect + '"';

                shortcode += ']';
                $('#customer-shortcode').text(shortcode);
            }

            $('#customer-theme, #customer-redirect').on('input change', updateCustomerShortcode);

            // === EMPLOYEE PORTAL ===
            function updateEmployeeShortcode() {
                let shortcode = '[bookando_employee_portal';
                const theme = $('#employee-theme').val();
                const redirect = $('#employee-redirect').val();

                if (theme) shortcode += ' theme=' + theme;
                if (redirect) shortcode += ' redirect_after_login="' + redirect + '"';

                shortcode += ']';
                $('#employee-shortcode').text(shortcode);
            }

            $('#employee-theme, #employee-redirect').on('input change', updateEmployeeShortcode);
        });

        // Copy to Clipboard
        function copyShortcode(type) {
            const shortcode = document.getElementById(type + '-shortcode').textContent;
            navigator.clipboard.writeText(shortcode).then(function() {
                alert('✅ Shortcode kopiert: ' + shortcode);
            }, function(err) {
                alert('❌ Fehler beim Kopieren');
            });
        }
        </script>
        <?php
    }
}
