<?php

namespace App\Actions\Pricing;

use App\Models\RepairPrice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Raises or lowers a set of prices by a percentage, e.g. +5% after a supplier
 * increase. Sale prices are left alone. Each change is recorded in the audit log.
 */
class AdjustRepairPrices
{
    public const string ROUND_PENNY = 'penny';

    public const string ROUND_POUND = 'pound';

    public const string ROUND_NINE = 'nine';

    /**
     * @var array<string, string>
     */
    public const array ROUNDING_OPTIONS = [
        self::ROUND_NINE => 'Up to the next price ending in 9 (e.g. £63.40 → £69)',
        self::ROUND_POUND => 'To the nearest whole pound',
        self::ROUND_PENNY => 'To the nearest penny',
    ];

    /**
     * @param  iterable<RepairPrice>  $prices
     * @return int how many prices changed
     */
    public function handle(iterable $prices, float $percent, string $rounding = self::ROUND_POUND): int
    {
        if ($percent <= -100.0) {
            throw new InvalidArgumentException('A price cannot be reduced by 100% or more.');
        }

        if (! array_key_exists($rounding, self::ROUNDING_OPTIONS)) {
            throw new InvalidArgumentException("Unknown rounding option [{$rounding}].");
        }

        return DB::transaction(function () use ($prices, $percent, $rounding): int {
            $changed = 0;

            foreach ($prices as $price) {
                $price->price_pence = $this->adjust($price->price_pence, $percent, $rounding);

                if ($price->isDirty('price_pence')) {
                    $price->save();
                    $changed++;
                }
            }

            return $changed;
        });
    }

    public function adjust(int $pence, float $percent, string $rounding): int
    {
        $adjusted = $pence * (1 + $percent / 100);

        return max(0, match ($rounding) {
            self::ROUND_NINE => (int) (ceil(($adjusted + 100) / 1000) * 1000 - 100),
            self::ROUND_POUND => (int) (round($adjusted / 100) * 100),
            default => (int) round($adjusted),
        });
    }
}
