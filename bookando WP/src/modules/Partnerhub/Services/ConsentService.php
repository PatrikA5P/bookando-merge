<?php

namespace Bookando\Modules\Partnerhub\Services;

use Bookando\Modules\Partnerhub\Models\PartnerConsentModel;
use Bookando\Modules\Partnerhub\Models\PartnerDataShareModel;
use Bookando\Modules\Partnerhub\Models\PartnerAuditLogModel;
use Bookando\Modules\Partnerhub\Models\PartnerModel;

/**
 * Consent Service
 *
 * DSGVO-konforme Verwaltung von Einwilligungen und Datenaustausch
 */
class ConsentService
{
    private PartnerConsentModel $consent_model;
    private PartnerDataShareModel $share_model;
    private PartnerAuditLogModel $audit_model;
    private PartnerModel $partner_model;

    public function __construct()
    {
        $this->consent_model = new PartnerConsentModel();
        $this->share_model = new PartnerDataShareModel();
        $this->audit_model = new PartnerAuditLogModel();
        $this->partner_model = new PartnerModel();
    }

    /**
     * Request consent from customer
     *
     * @param int $customer_id Customer ID
     * @param int $partner_id Partner ID
     * @param string $purpose Purpose of data sharing
     * @param array $data_categories Data categories to be shared
     * @param array $options Additional options
     * @return int Consent ID
     */
    public function request_consent(
        int $customer_id,
        int $partner_id,
        string $purpose,
        array $data_categories,
        array $options = []
    ): int {
        $partner = $this->partner_model->get_by_id($partner_id);

        if (!$partner) {
            throw new \Exception('Partner nicht gefunden');
        }

        // Check if valid contract exists
        if (!$this->partner_model->is_contract_valid($partner_id)) {
            throw new \Exception('Kein gültiger AVV mit Partner vorhanden');
        }

        // Generate consent text
        $consent_text = $this->generate_consent_text($partner, $purpose, $data_categories);

        // Calculate validity period
        $valid_from = $options['valid_from'] ?? current_time('mysql');
        $valid_until = $options['valid_until'] ?? date('Y-m-d H:i:s', strtotime('+' . $partner->data_retention_days . ' days'));

        $consent_data = [
            'customer_id' => $customer_id,
            'partner_id' => $partner_id,
            'purpose' => $purpose,
            'purpose_description' => $options['purpose_description'] ?? null,
            'data_categories' => $data_categories,
            'consent_given' => $options['consent_given'] ?? false,
            'consent_method' => $options['consent_method'] ?? 'explicit',
            'consent_text' => $consent_text,
            'consent_version' => '1.0',
            'consent_language' => $options['language'] ?? 'de',
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
            'legal_basis' => $options['legal_basis'] ?? 'consent',
        ];

        $consent_id = $this->consent_model->create_consent($consent_data);

        // Audit log
        $this->audit_model->log_consent_given($consent_id, $partner_id, $customer_id, $consent_data);

        return $consent_id;
    }

    /**
     * Grant consent
     */
    public function grant_consent(int $consent_id): bool
    {
        $consent = $this->consent_model->get_by_id($consent_id);

        if (!$consent) {
            throw new \Exception('Einwilligung nicht gefunden');
        }

        $result = $this->consent_model->update($consent_id, [
            'consent_given' => true,
            'consent_timestamp' => current_time('mysql'),
            'status' => 'active',
        ]);

        if ($result) {
            $this->audit_model->log_action('consent_granted', [
                'entity_type' => 'consent',
                'entity_id' => $consent_id,
                'partner_id' => $consent->partner_id,
                'data_subject_id' => $consent->customer_id,
                'involves_personal_data' => true,
            ]);
        }

        return $result;
    }

    /**
     * Revoke consent
     */
    public function revoke_consent(int $consent_id, string $reason = ''): bool
    {
        $consent = $this->consent_model->get_by_id($consent_id);

        if (!$consent) {
            throw new \Exception('Einwilligung nicht gefunden');
        }

        $result = $this->consent_model->revoke_consent($consent_id, $reason);

        if ($result) {
            $this->audit_model->log_consent_revoked(
                $consent_id,
                $consent->partner_id,
                $consent->customer_id,
                $reason
            );

            // Schedule deletion of shared data
            $this->schedule_data_deletion($consent->customer_id, $consent->partner_id);
        }

        return $result;
    }

    /**
     * Share customer data with partner (after consent check)
     */
    public function share_customer_data(
        int $customer_id,
        int $partner_id,
        string $purpose,
        array $data,
        ?int $booking_id = null
    ): int {
        // Check consent
        $data_categories = array_keys($data);

        if (!$this->consent_model->has_consent($customer_id, $partner_id, $purpose, $data_categories)) {
            throw new \Exception('Keine gültige Einwilligung für Datenweitergabe vorhanden');
        }

        // Get active consent
        $consent = $this->consent_model->get_active_consent($customer_id, $partner_id, $purpose);

        // Create data share record
        $share_id = $this->share_model->create_share(
            $partner_id,
            $consent->id,
            $customer_id,
            $data,
            "Datenweitergabe für: {$purpose}"
        );

        // Update booking reference if provided
        if ($booking_id) {
            $this->share_model->update($share_id, ['booking_id' => $booking_id]);
        }

        // Calculate deletion date (after purpose fulfillment)
        $partner = $this->partner_model->get_by_id($partner_id);
        $deletion_date = date('Y-m-d H:i:s', strtotime('+' . $partner->data_retention_days . ' days'));

        $this->share_model->update($share_id, [
            'scheduled_deletion_at' => $deletion_date,
            'transmission_status' => 'success',
        ]);

        // Audit log
        $this->audit_model->log_data_shared($partner_id, $customer_id, $data_categories, $purpose);

        return $share_id;
    }

    /**
     * Schedule deletion of shared data
     */
    private function schedule_data_deletion(int $customer_id, int $partner_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_partner_data_shares';

        $wpdb->update(
            $table,
            ['scheduled_deletion_at' => current_time('mysql')],
            [
                'customer_id' => $customer_id,
                'partner_id' => $partner_id,
                'deletion_confirmed' => 0,
            ]
        );
    }

    /**
     * Generate DSGVO-compliant consent text
     */
    private function generate_consent_text(object $partner, string $purpose, array $data_categories): string
    {
        $purpose_labels = [
            'booking' => 'Buchung von Dienstleistungen',
            'student_card' => 'Nutzung einer Schülerkarte',
            'course_enrollment' => 'Kursanmeldung',
            'event_participation' => 'Event-Teilnahme',
        ];

        $category_labels = [
            'name' => 'Name',
            'email' => 'E-Mail-Adresse',
            'phone' => 'Telefonnummer',
            'address' => 'Adresse',
            'birth_date' => 'Geburtsdatum',
            'student_id' => 'Schüler-ID',
        ];

        $purpose_text = $purpose_labels[$purpose] ?? $purpose;
        $categories_text = implode(', ', array_map(fn($c) => $category_labels[$c] ?? $c, $data_categories));

        return sprintf(
            "Ich willige ein, dass meine personenbezogenen Daten (%s) für den Zweck '%s' an den Partner '%s' (%s) weitergegeben werden.\n\n" .
            "Die Datenverarbeitung erfolgt auf Grundlage eines Auftragsverarbeitungsvertrags (AVV) gemäß Art. 28 DSGVO.\n\n" .
            "Rechtsgrundlage: Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)\n\n" .
            "Die Daten werden ausschließlich für den genannten Zweck verwendet und nach spätestens %d Tagen gelöscht.\n\n" .
            "Diese Einwilligung kann ich jederzeit mit Wirkung für die Zukunft widerrufen.",
            $categories_text,
            $purpose_text,
            $partner->name,
            $partner->company_name ?? $partner->website_url,
            $partner->data_retention_days
        );
    }

    /**
     * Get customer consent overview
     */
    public function get_customer_consent_overview(int $customer_id): array
    {
        $consents = $this->consent_model->get_customer_consents($customer_id, true);

        return array_map(function ($consent) {
            $partner = $this->partner_model->get_by_id($consent->partner_id);

            return [
                'consent_id' => $consent->id,
                'partner_name' => $partner->name ?? 'Unbekannt',
                'purpose' => $consent->purpose,
                'data_categories' => json_decode($consent->data_categories, true),
                'granted_at' => $consent->consent_timestamp,
                'valid_until' => $consent->valid_until,
                'can_revoke' => true,
            ];
        }, $consents);
    }

    /**
     * Process data deletion requests (DSGVO Art. 17)
     */
    public function process_deletion_requests(): int
    {
        $shares = $this->share_model->get_shares_for_deletion();
        $deleted = 0;

        foreach ($shares as $share) {
            // In production, this would trigger API calls to partners
            // to confirm data deletion on their side

            $this->share_model->update($share->id, [
                'deleted_from_partner_at' => current_time('mysql'),
                'deletion_confirmed' => true,
            ]);

            $this->audit_model->log_action('data_deleted', [
                'partner_id' => $share->partner_id,
                'entity_type' => 'data_share',
                'entity_id' => $share->id,
                'data_subject_id' => $share->customer_id,
                'involves_personal_data' => true,
                'description' => 'Kundendaten beim Partner gelöscht',
            ]);

            $deleted++;
        }

        return $deleted;
    }
}
