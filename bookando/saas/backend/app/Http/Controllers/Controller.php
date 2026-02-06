<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Get the tenant ID of the currently authenticated user.
     */
    protected function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }
}
