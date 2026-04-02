<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminOnlyPhoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPhoneSettingsController extends Controller
{
    public function __construct(
        protected AdminOnlyPhoneService $adminOnlyPhones
    ) {}

    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $rows = $this->adminOnlyPhones->allOrdered();
        $textarea = $rows->map(function ($r) {
            return $r->label
                ? $r->phone.', '.$r->label
                : $r->phone;
        })->implode("\n");

        return response()->json([
            'phones_text' => $textarea,
            'count' => $rows->count(),
            'items' => $rows->map(fn ($r) => [
                'phone' => $r->phone,
                'label' => $r->label,
            ])->values(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'phones' => ['nullable', 'string', 'max:65535'],
            'items' => ['nullable', 'array', 'max:500'],
            'items.*.phone' => ['required', 'string', 'max:32'],
            'items.*.label' => ['nullable', 'string', 'max:255'],
        ]);

        // Mobile sends `items: []` to clear. Do not use $request->has('items') — Laravel treats
        // empty arrays as "missing". Prefer validated payload.
        if (array_key_exists('items', $data) && is_array($data['items'])) {
            $this->adminOnlyPhones->syncFromItems($data['items']);
        } else {
            $lines = preg_split("/\r\n|\n|\r/", $data['phones'] ?? '') ?: [];
            $this->adminOnlyPhones->syncFromLines($lines);
        }

        $count = $this->adminOnlyPhones->allOrdered()->count();

        return response()->json([
            'message' => 'Restricted numbers saved.',
            'count' => $count,
        ]);
    }
}
