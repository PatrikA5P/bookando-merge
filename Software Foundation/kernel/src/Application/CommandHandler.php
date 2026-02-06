<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

interface CommandHandler
{
    public function handle(Command $command, SecurityContext $context): mixed;
}
