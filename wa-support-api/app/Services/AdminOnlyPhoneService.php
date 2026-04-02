<?php

namespace App\Services;

use App\Models\AdminOnlyPhone;
use App\Models\Conversation;
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
    /**
     * Structured sync from mobile (phone + optional label per row).
     *
     * @param  array<int, array{phone?: mixed, label?: mixed}>  $items
     */
    public function syncFromItems(array $items): void
    {
        $before = $this->restrictedPhoneList();

        $rows = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $raw = (string) ($row['phone'] ?? '');
            $phone = Phone::normalize($raw);
            if ($phone === '') {
                continue;
            }
            $label = $row['label'] ?? null;
            $label = is_string($label) && $label !== '' ? mb_substr(trim($label), 0, 255) : null;
            $rows[$phone] = ['phone' => $phone, 'label' => $label];
        }

        DB::transaction(function () use ($rows) {
            AdminOnlyPhone::query()->delete();
            foreach ($rows as $row) {
                AdminOnlyPhone::query()->create($row);
            }
        });

        $this->releaseAgentAssignmentsForPhonesNoLongerRestricted($before, array_keys($rows));
    }

    public function syncFromLines(array $lines): void
    {
        $before = $this->restrictedPhoneList();

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

        $this->releaseAgentAssignmentsForPhonesNoLongerRestricted($before, array_keys($rows));
    }

    /**
     * When a number is removed from the restricted list, clear assignment so the thread
     * returns to the shared agent inbox (unassigned pool). While restricted, threads are
     * often assigned to an admin — agents are excluded by both filters.
     *
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    protected function releaseAgentAssignmentsForPhonesNoLongerRestricted(array $before, array $after): void
    {
        foreach (array_diff($before, $after) as $phone) {
            Conversation::query()->where('phone', $phone)->update(['assigned_to' => null]);
        }
    }
}
