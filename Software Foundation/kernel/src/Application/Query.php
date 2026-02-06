<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

/**
 * Marker interface for all queries (read operations).
 * Every query MUST carry tenantId.
 */
interface Query
{
    public function tenantId(): int;
}
