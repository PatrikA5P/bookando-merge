<?php

declare(strict_types=1);

namespace Bookando\Core;

function file_exists(string $filename): bool
{
    if (defined('BOOKANDO_TEST_FORCE_CASE_SENSITIVE') && BOOKANDO_TEST_FORCE_CASE_SENSITIVE) {
        $real = \realpath($filename);

        if ($real === false) {
            return false;
        }

        $normalizedReal  = str_replace('\\', '/', $real);
        $normalizedGiven = str_replace('\\', '/', $filename);

        return $normalizedReal === $normalizedGiven;
    }

    return \file_exists($filename);
}
