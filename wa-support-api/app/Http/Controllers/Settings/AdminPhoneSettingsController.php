<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AdminOnlyPhoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPhoneSettingsController extends Controller
{
    public function __construct(
        protected AdminOnlyPhoneService $adminOnlyPhones
    ) {}

    public function edit(): View
    {
        $this->authorizeAdmin();

        $rows = $this->adminOnlyPhones->allOrdered();
        $textarea = $rows->map(function ($r) {
            return $r->label
                ? $r->phone.', '.$r->label
                : $r->phone;
        })->implode("\n");

        return view('settings.admin-phones', [
            'phoneLines' => $textarea,
            'count' => $rows->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'phones' => ['nullable', 'string', 'max:65535'],
        ]);

        $lines = preg_split("/\r\n|\n|\r/", $data['phones'] ?? '') ?: [];
        $this->adminOnlyPhones->syncFromLines($lines);

        return redirect()
            ->route('settings.admin-phones')
            ->with('status', __('Restricted numbers saved.'));
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
