<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/settings
     *
     * All settings for the tenant as a key-value object.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $settings = Setting::where('tenant_id', $tenantId)->get();

        $keyValues = [];
        foreach ($settings as $setting) {
            $keyValues[$setting->key] = $setting->value;
        }

        return response()->json([
            'data' => $keyValues,
        ]);
    }

    /**
     * PUT /api/v1/settings
     *
     * Accepts an object of key-value pairs and upserts each.
     */
    public function update(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $pairs = $validated['settings'];

        foreach ($pairs as $key => $value) {
            Setting::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        // Return the full settings object after update
        $settings = Setting::where('tenant_id', $tenantId)->get();

        $keyValues = [];
        foreach ($settings as $setting) {
            $keyValues[$setting->key] = $setting->value;
        }

        return response()->json([
            'data' => $keyValues,
        ]);
    }
}
