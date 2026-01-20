<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend\Admin;

/**
 * Link Generator Page - UTM Parameters & Tracking
 *
 * Generates trackable SaaS/Cloud links for marketing campaigns
 */
class LinkGeneratorPage
{
    public static function render(): void
    {
        ?>
        <div class="wrap">
            <h1>🔗 SaaS Link Generator</h1>
            <p class="description">
                Erstellen Sie trackbare Links für Marketing-Kampagnen mit UTM-Parametern.
                Perfekt für Facebook, Google Ads, Email-Marketing, etc.
            </p>

            <div class="bookando-link-generator" style="margin-top: 30px;">

                <!-- Tab Navigation -->
                <h2 class="nav-tab-wrapper">
                    <a href="#generator" class="nav-tab nav-tab-active" data-tab="generator">🎯 Link erstellen</a>
                    <a href="#history" class="nav-tab" data-tab="history">📊 Link-Historie</a>
                    <a href="#analytics" class="nav-tab" data-tab="analytics">📈 Analytics</a>
                </h2>

                <!-- Generator Tab -->
                <div id="generator" class="tab-content active" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                    <h2>Link Generator</h2>

                    <table class="form-table">
                        <tr>
                            <th><label>🎯 Target Type</label></th>
                            <td>
                                <select id="link-target-type" class="regular-text">
                                    <option value="catalog">Catalog View (Angebotskatalog)</option>
                                    <option value="booking">Booking Widget (Buchungs-Wizard)</option>
                                    <option value="list">List View (Listen-Ansicht)</option>
                                    <option value="calendar">Calendar View (Kalender)</option>
                                    <option value="offer">Einzelnes Angebot</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3>Filter & Parameter</h3>
                    <table class="form-table">
                        <tr>
                            <th><label>Kategorie-IDs</label></th>
                            <td>
                                <input type="text" id="link-category" class="regular-text" placeholder="z.B. 1,2,3">
                                <p class="description">Komma-getrennte Kategorie-IDs</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Tag-IDs</label></th>
                            <td>
                                <input type="text" id="link-tag" class="regular-text" placeholder="z.B. {1,2}">
                                <p class="description">Tags in geschweiften Klammern</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Mitarbeiter-ID</label></th>
                            <td>
                                <input type="text" id="link-employee" class="small-text" placeholder="z.B. 5">
                            </td>
                        </tr>
                        <tr>
                            <th><label>Standort-ID</label></th>
                            <td>
                                <input type="text" id="link-location" class="small-text" placeholder="z.B. 1">
                            </td>
                        </tr>
                        <tr>
                            <th><label>Angebots-ID</label></th>
                            <td>
                                <input type="text" id="link-offer" class="regular-text" placeholder="z.B. 123 oder 1,2,3">
                                <p class="description">Nur für Target Type "Einzelnes Angebot" oder als Filter</p>
                            </td>
                        </tr>
                    </table>

                    <h3>📊 UTM-Parameter (Kampagnen-Tracking)</h3>
                    <table class="form-table">
                        <tr>
                            <th><label>UTM Source *</label></th>
                            <td>
                                <input type="text" id="link-utm-source" class="regular-text" placeholder="z.B. facebook, google, newsletter">
                                <p class="description">Quelle des Traffics (erforderlich)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>UTM Medium</label></th>
                            <td>
                                <input type="text" id="link-utm-medium" class="regular-text" placeholder="z.B. cpc, email, social">
                                <p class="description">Marketing-Medium</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>UTM Campaign</label></th>
                            <td>
                                <input type="text" id="link-utm-campaign" class="regular-text" placeholder="z.B. summer_2024, black_friday">
                                <p class="description">Kampagnen-Name</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>UTM Term</label></th>
                            <td>
                                <input type="text" id="link-utm-term" class="regular-text" placeholder="z.B. fahrschule+zürich">
                                <p class="description">Suchbegriffe (für Paid Search)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>UTM Content</label></th>
                            <td>
                                <input type="text" id="link-utm-content" class="regular-text" placeholder="z.B. logo_link, banner_top">
                                <p class="description">Anzeigen-/Content-Variante (für A/B Tests)</p>
                            </td>
                        </tr>
                    </table>

                    <h3>⚙️ Zusätzliche Optionen</h3>
                    <table class="form-table">
                        <tr>
                            <th><label>Ablaufdatum</label></th>
                            <td>
                                <input type="date" id="link-expires" class="regular-text">
                                <p class="description">Optional: Link läuft ab nach diesem Datum</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>QR-Code generieren</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="link-generate-qr" checked>
                                    QR-Code für Offline-Marketing erstellen
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button button-primary button-hero" id="generate-link-btn">
                            🚀 Link generieren
                        </button>
                    </p>

                    <!-- Result Area -->
                    <div id="generated-link-result" style="display: none; margin-top: 30px; background: #f0f8ff; padding: 20px; border-radius: 4px; border: 2px solid #0073aa;">
                        <h3>✅ Link erfolgreich erstellt!</h3>

                        <h4>📋 Vollständiger Link:</h4>
                        <div style="background: white; padding: 15px; border-radius: 4px; font-family: monospace; word-break: break-all; position: relative;">
                            <code id="result-full-link" style="display: block;"></code>
                            <button type="button" class="button button-small" style="position: absolute; top: 10px; right: 10px;" onclick="copyToClipboard('result-full-link')">
                                📋 Kopieren
                            </button>
                        </div>

                        <h4 style="margin-top: 20px;">🔗 Kurz-Link:</h4>
                        <div style="background: white; padding: 15px; border-radius: 4px; font-family: monospace; position: relative;">
                            <code id="result-short-link" style="display: block;"></code>
                            <button type="button" class="button button-small" style="position: absolute; top: 10px; right: 10px;" onclick="copyToClipboard('result-short-link')">
                                📋 Kopieren
                            </button>
                        </div>

                        <div id="qr-code-container" style="margin-top: 20px; text-align: center;">
                            <h4>📱 QR-Code:</h4>
                            <canvas id="qr-code-canvas"></canvas>
                            <br>
                            <button type="button" class="button button-secondary" onclick="downloadQRCode()">
                                💾 QR-Code herunterladen
                            </button>
                        </div>

                        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                            <strong>💡 Tipp:</strong> Verwenden Sie den Kurz-Link für Social Media und Print-Marketing.
                            Der vollständige Link zeigt alle Parameter transparent.
                        </div>
                    </div>
                </div>

                <!-- Link Historie Tab -->
                <div id="history" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>📊 Link-Historie</h2>
                    <p>Alle generierten Marketing-Links mit Performance-Daten.</p>

                    <table class="wp-list-table widefat fixed striped" id="links-table">
                        <thead>
                            <tr>
                                <th>Link</th>
                                <th>Kampagne</th>
                                <th>Quelle</th>
                                <th>Klicks</th>
                                <th>Conversions</th>
                                <th>CR %</th>
                                <th>Erstellt</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody id="links-table-body">
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <em>Lade Links...</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Analytics Tab -->
                <div id="analytics" class="tab-content" style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-top: none; display: none;">
                    <h2>📈 Link Analytics</h2>

                    <div class="analytics-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
                        <div class="stat-card" style="background: #f0f8ff; padding: 20px; border-radius: 4px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa; font-size: 36px;" id="stat-total-links">0</h3>
                            <p style="margin: 10px 0 0; color: #666;">Gesamt Links</p>
                        </div>
                        <div class="stat-card" style="background: #f0fff4; padding: 20px; border-radius: 4px; text-align: center;">
                            <h3 style="margin: 0; color: #46b450; font-size: 36px;" id="stat-total-clicks">0</h3>
                            <p style="margin: 10px 0 0; color: #666;">Gesamt Klicks</p>
                        </div>
                        <div class="stat-card" style="background: #fff8f0; padding: 20px; border-radius: 4px; text-align: center;">
                            <h3 style="margin: 0; color: #f56e28; font-size: 36px;" id="stat-total-conversions">0</h3>
                            <p style="margin: 10px 0 0; color: #666;">Conversions</p>
                        </div>
                        <div class="stat-card" style="background: #fff0f7; padding: 20px; border-radius: 4px; text-align: center;">
                            <h3 style="margin: 0; color: #dc3232; font-size: 36px;" id="stat-avg-cr">0%</h3>
                            <p style="margin: 10px 0 0; color: #666;">Ø Conversion Rate</p>
                        </div>
                    </div>

                    <h3>Top Performer</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Kampagne</th>
                                <th>Quelle/Medium</th>
                                <th>Klicks</th>
                                <th>Conversions</th>
                                <th>CR %</th>
                            </tr>
                        </thead>
                        <tbody id="top-links-body">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">
                                    <em>Keine Daten verfügbar</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <style>
            .tab-content {
                display: none;
            }
            .tab-content.active {
                display: block;
            }
            .nav-tab-active {
                border-bottom: 1px solid white;
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

                $('.tab-content').removeClass('active');
                $('#' + tab).addClass('active');

                // Load data when switching to history or analytics
                if (tab === 'history') {
                    loadLinkHistory();
                } else if (tab === 'analytics') {
                    loadAnalytics();
                }
            });

            // Generate Link
            $('#generate-link-btn').on('click', function() {
                const data = {
                    target_type: $('#link-target-type').val(),
                    category: $('#link-category').val(),
                    tag: $('#link-tag').val(),
                    employee: $('#link-employee').val(),
                    location: $('#link-location').val(),
                    offer: $('#link-offer').val(),
                    utm_source: $('#link-utm-source').val(),
                    utm_medium: $('#link-utm-medium').val(),
                    utm_campaign: $('#link-utm-campaign').val(),
                    utm_term: $('#link-utm-term').val(),
                    utm_content: $('#link-utm-content').val(),
                    expires: $('#link-expires').val(),
                    generate_qr: $('#link-generate-qr').is(':checked'),
                };

                if (!data.utm_source) {
                    alert('❌ UTM Source ist erforderlich!');
                    $('#link-utm-source').focus();
                    return;
                }

                // Call REST API
                $.ajax({
                    url: '<?php echo rest_url('bookando/v1/frontend/links/generate'); ?>',
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    success: function(response) {
                        $('#result-full-link').text(response.full_link);
                        $('#result-short-link').text(response.short_link);

                        if (response.qr_code) {
                            generateQRCodeCanvas(response.short_link);
                            $('#qr-code-container').show();
                        } else {
                            $('#qr-code-container').hide();
                        }

                        $('#generated-link-result').slideDown();

                        $('html, body').animate({
                            scrollTop: $('#generated-link-result').offset().top - 50
                        }, 500);
                    },
                    error: function(xhr) {
                        alert('❌ Fehler: ' + (xhr.responseJSON?.message || 'Unbekannter Fehler'));
                    }
                });
            });

            // Load link history
            function loadLinkHistory() {
                $.ajax({
                    url: '<?php echo rest_url('bookando/v1/frontend/links'); ?>',
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    success: function(response) {
                        const tbody = $('#links-table-body');
                        tbody.empty();

                        if (!response.links || response.links.length === 0) {
                            tbody.append('<tr><td colspan="8" style="text-align: center; padding: 20px;"><em>Keine Links vorhanden</em></td></tr>');
                            return;
                        }

                        response.links.forEach(function(link) {
                            const cr = link.click_count > 0 ? ((link.conversion_count / link.click_count) * 100).toFixed(1) : '0.0';
                            const row = `
                                <tr>
                                    <td><code style="font-size: 11px;">${link.link_hash}</code></td>
                                    <td>${link.utm_campaign || '-'}</td>
                                    <td>${link.utm_source}${link.utm_medium ? ' / ' + link.utm_medium : ''}</td>
                                    <td>${link.click_count}</td>
                                    <td>${link.conversion_count}</td>
                                    <td>${cr}%</td>
                                    <td>${new Date(link.created_at).toLocaleDateString('de-DE')}</td>
                                    <td>
                                        <button class="button button-small" onclick="copyToClipboard('link-${link.id}')">📋</button>
                                        <span id="link-${link.id}" style="display: none;">${link.short_link}</span>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    }
                });
            }

            // Load analytics
            function loadAnalytics() {
                $.ajax({
                    url: '<?php echo rest_url('bookando/v1/frontend/links/analytics'); ?>',
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    success: function(response) {
                        $('#stat-total-links').text(response.total_links);
                        $('#stat-total-clicks').text(response.total_clicks);
                        $('#stat-total-conversions').text(response.total_conversions);
                        $('#stat-avg-cr').text(response.avg_cr + '%');

                        // Top links
                        const tbody = $('#top-links-body');
                        tbody.empty();

                        if (!response.top_links || response.top_links.length === 0) {
                            tbody.append('<tr><td colspan="5" style="text-align: center; padding: 20px;"><em>Keine Daten verfügbar</em></td></tr>');
                            return;
                        }

                        response.top_links.forEach(function(link) {
                            const cr = link.click_count > 0 ? ((link.conversion_count / link.click_count) * 100).toFixed(1) : '0.0';
                            const row = `
                                <tr>
                                    <td><strong>${link.utm_campaign || '-'}</strong></td>
                                    <td>${link.utm_source}${link.utm_medium ? ' / ' + link.utm_medium : ''}</td>
                                    <td>${link.click_count}</td>
                                    <td>${link.conversion_count}</td>
                                    <td><strong>${cr}%</strong></td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    }
                });
            }
        });

        // Helper functions
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            navigator.clipboard.writeText(text).then(function() {
                alert('✅ Link kopiert!');
            }, function(err) {
                alert('❌ Fehler beim Kopieren');
            });
        }

        function generateQRCodeCanvas(url) {
            // Simplified QR code generation (in production use proper library like qrcode.js)
            const canvas = document.getElementById('qr-code-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 200;
            canvas.height = 200;
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, 200, 200);
            ctx.fillStyle = 'black';
            ctx.font = '12px Arial';
            ctx.fillText('QR Code:', 10, 20);
            ctx.fillText(url.substring(0, 30) + '...', 10, 40);
            ctx.fillText('(Integration pending)', 10, 180);
        }

        function downloadQRCode() {
            const canvas = document.getElementById('qr-code-canvas');
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = url;
            a.download = 'bookando-qr-code.png';
            a.click();
        }
        </script>
        <?php
    }
}
