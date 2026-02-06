<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Consent;

/**
 * Purposes for which consent can be granted.
 *
 * DSG Art. 6 / DSGVO Art. 6/7 require explicit consent for specific purposes.
 * Consent must be voluntary, informed, and revocable.
 */
enum ConsentPurpose: string
{
    /** Storage and processing of personal photos/portraits (DSG). */
    case PHOTO_STORAGE = 'photo_storage';

    /** Processing of personal data for employment purposes. */
    case EMPLOYEE_DATA = 'employee_data';

    /** Marketing communications (email, SMS, push). */
    case MARKETING = 'marketing';

    /** Analytics and usage tracking. */
    case ANALYTICS = 'analytics';

    /** Sharing data with third-party services. */
    case THIRD_PARTY_SHARING = 'third_party_sharing';

    /** Biometric data processing (fingerprint, face recognition). */
    case BIOMETRIC = 'biometric';

    /** Transfer of data to countries without adequate protection level. */
    case CROSS_BORDER_TRANSFER = 'cross_border_transfer';

    /** Profiling and automated individual decision-making. */
    case PROFILING = 'profiling';

    /**
     * Whether this consent purpose involves sensitive personal data (DSG Art. 5).
     */
    public function isSensitiveData(): bool
    {
        return match ($this) {
            self::PHOTO_STORAGE, self::BIOMETRIC, self::PROFILING => true,
            default => false,
        };
    }

    /**
     * Whether explicit (not implied) consent is required.
     */
    public function requiresExplicitConsent(): bool
    {
        return $this->isSensitiveData()
            || $this === self::CROSS_BORDER_TRANSFER
            || $this === self::THIRD_PARTY_SHARING;
    }
}
