<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

interface QueryHandler
{
    public function handle(Query $query, SecurityContext $context): mixed;
}
