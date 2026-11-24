<?php

namespace App\Support;

use App\Models\ReferenceSequence;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Human-friendly, sequential references that restart each year:
 * jobs "ZR-2026-000123", leads "ENQ-2026-000045".
 *
 * The sequence row is locked inside a transaction, so concurrent bookings
 * never receive the same number.
 */
final class ReferenceGenerator
{
    public const string JOB = 'job';

    public const string LEAD = 'lead';

    public function next(string $sequence, string $prefix, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return sprintf('%s-%d-%06d', $prefix, $year, $this->nextNumber($sequence, $year));
    }

    private function nextNumber(string $sequence, int $year): int
    {
        return DB::transaction(function () use ($sequence, $year): int {
            $row = ReferenceSequence::query()
                ->where('name', $sequence)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                try {
                    $row = ReferenceSequence::query()->create(['name' => $sequence, 'year' => $year, 'last_number' => 0]);
                } catch (UniqueConstraintViolationException) {
                    // Another request created the row first: lock theirs instead.
                    $row = ReferenceSequence::query()->where('name', $sequence)->where('year', $year)->lockForUpdate()->firstOrFail();
                }
            }

            $row->increment('last_number');

            return $row->last_number;
        });
    }
}
