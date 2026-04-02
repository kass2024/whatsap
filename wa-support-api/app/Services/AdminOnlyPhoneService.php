<?php

namespace App\Services;

use App\Models\AdminOnlyPhone;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AdminOnlyPhoneService
{
    /**
     * @return list<string>
     */
    public function restrictedPhoneList(): array
    {
        return AdminOnlyPhone::query()->pluck('phone')->all();
    }

    public function isRestricted(string $phone): bool
    {
        $n = Phone::normalize($phone);

        return $n !== '' && AdminOnlyPhone::query()->where('phone', $n)->exists();
    }

    /**
     * @return Collection<int, AdminOnlyPhone>
     */
    public function allOrdered(): Collection
    {
        return AdminOnlyPhone::query()->orderBy('phone')->get();
    }

    /**
     * Lines: optional "phone, label" or "phone" per line.
     *
     * @param  list<string>  $lines
     */
    public function syncFromLines(array $lines): void
    {
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $label = null;
            if (str_contains($line, ',')) {
                [$rawPhone, $labelPart] = array_map('trim', explode(',', $line, 2));
                $label = $labelPart !== '' ? $labelPart : null;
            } else {
                $rawPhone = $line;
            }
            $phone = Phone::normalize($rawPhone);
            if ($phone === '') {
                continue;
            }
            $rows[$phone] = ['phone' => $phone, 'label' => $label];
        }

        DB::transaction(function () use ($rows) {
            AdminOnlyPhone::query()->delete();
            foreach ($rows as $row) {
                AdminOnlyPhone::query()->create($row);
            }
        });
    }
}
