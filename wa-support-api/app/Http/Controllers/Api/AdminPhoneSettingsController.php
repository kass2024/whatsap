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
        ]);

        $lines = preg_split("/\r\n|\n|\r/", $data['phones'] ?? '') ?: [];
        $this->adminOnlyPhones->syncFromLines($lines);

        $count = $this->adminOnlyPhones->allOrdered()->count();

        return response()->json([
            'message' => 'Restricted numbers saved.',
            'count' => $count,
        ]);
    }
}
