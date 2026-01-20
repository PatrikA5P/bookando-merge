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
            <h1>📋 Shortcode Generator</h1>
            <p class="description">
                Erstellen Sie Shortcodes für Ihre Website - kopieren Sie einfach den generierten Code.
            </p>

            <div class="bookando-shortcode-generator" style="margin-top: 30px;">

                <!-- Tab Navigation -->
                <h2 class="nav-tab-wrapper">
                    <a href="#offers" class="nav-tab nav-tab-active" data-tab="offers">🎯 Angebote</a>
                    <a href="#customer-portal" class="nav-tab" data-tab="customer-portal">👤 Kundenportal</a>
                    <a href="#employee-portal" class="nav-tab" data-tab="employee-portal">👔 Mitarbeiterportal</a>
                    <a href="#booking" class="nav-tab" data-tab="booking">📅 Buchungs-Widget</a>
                </h2>

                <!-- Offers Tab -->
                <div id="offers" class="tab-content active" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                    <h2>Angebote anzeigen</h2>
                    <p>Zeigen Sie Ihre Kurse, Pakete und Angebote auf jeder Seite an.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Kategorie</label></th>
                            <td>
                                <input type="text" id="offers-category" class="regular-text" placeholder="z.B. driving, theory">
                                <p class="description">Optional: Filter nach Kategorie</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Tag</label></th>
                            <td>
                                <input type="text" id="offers-tag" class="regular-text" placeholder="z.B. beginner, advanced">
                                <p class="description">Optional: Filter nach Tag</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Spezifische IDs</label></th>
                            <td>
                                <input type="text" id="offers-ids" class="regular-text" placeholder="z.B. 1,2,3">
                                <p class="description">Optional: Nur bestimmte Angebote anzeigen (Komma-getrennt)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Layout</label></th>
                            <td>
                                <select id="offers-layout">
                                    <option value="grid">Grid (Karten)</option>
                                    <option value="list">Liste</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Spalten</label></th>
                            <td>
                                <select id="offers-columns">
                                    <option value="2">2 Spalten</option>
                                    <option value="3" selected>3 Spalten</option>
                                    <option value="4">4 Spalten</option>
                                </select>
                                <p class="description">Nur bei Grid-Layout</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Nur Featured</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="offers-featured">
                                    Nur hervorgehobene Angebote anzeigen
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Limit</label></th>
                            <td>
                                <input type="number" id="offers-limit" value="12" min="1" max="100" style="width: 80px;">
                                <p class="description">Maximale Anzahl der Angebote</p>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="offers-shortcode" style="display: block; word-break: break-all;">[bookando_offers]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('offers')">
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
                                <td><code>[bookando_offers]</code></td>
                            </tr>
                            <tr>
                                <td>Nur Fahrschule-Kategorie</td>
                                <td><code>[bookando_offers category="driving"]</code></td>
                            </tr>
                            <tr>
                                <td>Anfänger-Kurse im Grid</td>
                                <td><code>[bookando_offers tag="beginner" layout="grid" columns="3"]</code></td>
                            </tr>
                            <tr>
                                <td>Featured Angebote als Liste</td>
                                <td><code>[bookando_offers featured="true" layout="list"]</code></td>
                            </tr>
                            <tr>
                                <td>Spezifische Angebote</td>
                                <td><code>[bookando_offers ids="1,2,3"]</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Customer Portal Tab -->
                <div id="customer-portal" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>Kundenportal</h2>
                    <p>Geben Sie Ihren Kunden Zugang zu ihrem persönlichen Portal.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Theme</label></th>
                            <td>
                                <select id="customer-theme">
                                    <option value="light">Hell</option>
                                    <option value="dark">Dunkel</option>
                                </select>
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
                </div>

                <!-- Employee Portal Tab -->
                <div id="employee-portal" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>Mitarbeiterportal</h2>
                    <p>Portal für Fahrlehrer und Mitarbeiter.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Theme</label></th>
                            <td>
                                <select id="employee-theme">
                                    <option value="light">Hell</option>
                                    <option value="dark">Dunkel</option>
                                </select>
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
                </div>

                <!-- Booking Widget Tab -->
                <div id="booking" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>Buchungs-Widget</h2>
                    <p>Ermöglichen Sie direkte Buchungen für ein spezifisches Angebot.</p>

                    <table class="form-table">
                        <tr>
                            <th><label>Angebots-ID</label></th>
                            <td>
                                <input type="text" id="booking-offer-id" class="regular-text" placeholder="z.B. 123" required>
                                <p class="description">Die ID des Angebots (erforderlich)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Angebotstyp</label></th>
                            <td>
                                <select id="booking-offer-type">
                                    <option value="course">Kurs</option>
                                    <option value="appointment">Termin</option>
                                    <option value="package">Paket</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Theme</label></th>
                            <td>
                                <select id="booking-theme">
                                    <option value="light">Hell</option>
                                    <option value="dark">Dunkel</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Details anzeigen</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="booking-show-details" checked>
                                    Angebotsdetails anzeigen
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3>📋 Generierter Shortcode:</h3>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                        <code id="booking-shortcode" style="display: block; word-break: break-all;">[bookando_booking offer_id=""]</code>
                        <button type="button" class="button button-primary" style="position: absolute; top: 10px; right: 10px;" onclick="copyShortcode('booking')">
                            📋 Kopieren
                        </button>
                    </div>
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

            // Offers Shortcode Generator
            function updateOffersShortcode() {
                let shortcode = '[bookando_offers';
                const category = $('#offers-category').val();
                const tag = $('#offers-tag').val();
                const ids = $('#offers-ids').val();
                const layout = $('#offers-layout').val();
                const columns = $('#offers-columns').val();
                const featured = $('#offers-featured').is(':checked');
                const limit = $('#offers-limit').val();

                if (category) shortcode += ' category="' + category + '"';
                if (tag) shortcode += ' tag="' + tag + '"';
                if (ids) shortcode += ' ids="' + ids + '"';
                if (layout !== 'grid') shortcode += ' layout="' + layout + '"';
                if (columns !== '3') shortcode += ' columns="' + columns + '"';
                if (featured) shortcode += ' featured="true"';
                if (limit !== '12') shortcode += ' limit="' + limit + '"';

                shortcode += ']';
                $('#offers-shortcode').text(shortcode);
            }

            $('#offers-category, #offers-tag, #offers-ids, #offers-layout, #offers-columns, #offers-limit').on('input change', updateOffersShortcode);
            $('#offers-featured').on('change', updateOffersShortcode);

            // Customer Portal Shortcode Generator
            function updateCustomerShortcode() {
                let shortcode = '[bookando_customer_portal';
                const theme = $('#customer-theme').val();
                const redirect = $('#customer-redirect').val();

                if (theme !== 'light') shortcode += ' theme="' + theme + '"';
                if (redirect) shortcode += ' redirect_after_login="' + redirect + '"';

                shortcode += ']';
                $('#customer-shortcode').text(shortcode);
            }

            $('#customer-theme, #customer-redirect').on('input change', updateCustomerShortcode);

            // Employee Portal Shortcode Generator
            function updateEmployeeShortcode() {
                let shortcode = '[bookando_employee_portal';
                const theme = $('#employee-theme').val();
                const redirect = $('#employee-redirect').val();

                if (theme !== 'light') shortcode += ' theme="' + theme + '"';
                if (redirect) shortcode += ' redirect_after_login="' + redirect + '"';

                shortcode += ']';
                $('#employee-shortcode').text(shortcode);
            }

            $('#employee-theme, #employee-redirect').on('input change', updateEmployeeShortcode);

            // Booking Widget Shortcode Generator
            function updateBookingShortcode() {
                let shortcode = '[bookando_booking';
                const offerId = $('#booking-offer-id').val();
                const offerType = $('#booking-offer-type').val();
                const theme = $('#booking-theme').val();
                const showDetails = $('#booking-show-details').is(':checked');

                if (offerId) shortcode += ' offer_id="' + offerId + '"';
                if (offerType !== 'course') shortcode += ' offer_type="' + offerType + '"';
                if (theme !== 'light') shortcode += ' theme="' + theme + '"';
                if (!showDetails) shortcode += ' show_details="false"';

                shortcode += ']';
                $('#booking-shortcode').text(shortcode);
            }

            $('#booking-offer-id, #booking-offer-type, #booking-theme').on('input change', updateBookingShortcode);
            $('#booking-show-details').on('change', updateBookingShortcode);
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
