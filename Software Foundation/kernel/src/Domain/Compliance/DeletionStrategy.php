<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Compliance;

/**
 * Strategies for data deletion in compliance with DSG/DSGVO.
 *
 * The choice of strategy depends on the data category and legal requirements:
 * - Financial data under retention cannot be hard-deleted.
 * - Personal data must be deletable on request (DSGVO Art. 17).
 * - Crypto-shredding is the preferred approach for encrypted personal data.
 */
enum DeletionStrategy: string
{
    case HARD_DELETE = 'hard_delete';    // Physical removal from DB
    case SOFT_DELETE = 'soft_delete';    // Mark as deleted, retain data
    case CRYPTO_SHRED = 'crypto_shred'; // Delete encryption key, data becomes unreadable
    case ANONYMIZE = 'anonymize';        // Replace PII with anonymized data
}
