<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Ensure the user is authenticated and is an admin.
     */
    private function requireAdmin(): void
    {
        if (!auth()->check()) {
            abort(response()->json(['success' => false, 'message' => 'Unauthenticated'], 401));
        }
        if (auth()->user()->role_id !== 1) {
            abort(response()->json(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403));
        }
    }

    /**
     * Get all settings (admin only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->requireAdmin();
        $settings = Setting::all();
        return response()->json(['success' => true, 'data' => $settings]);
    }

    /**
     * Get a specific setting by key (admin only).
     *
     * @param string $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($key)
    {
        $this->requireAdmin();
        $setting = Setting::find($key);
        if (!$setting) {
            return response()->json(['success' => false, 'message' => 'Setting not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $setting]);
    }

    /**
     * Update or create settings in bulk (admin only).
     * Expected input: { "settings": [ { "key": "...", "value": ... } ] }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $this->requireAdmin();

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updated = [];
        foreach ($request->settings as $item) {
            $setting = Setting::set($item['key'], $item['value']);
            $updated[] = $setting->key;
        }

        return response()->json([
            'success' => true,
            'message' => count($updated) . ' settings updated',
            'updated_keys' => $updated,
        ]);
    }
}
