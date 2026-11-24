<?php

namespace App\Actions\Pricing;

use App\Data\PriceImportResult;
use App\Models\DeviceModel;
use App\Models\PartQuality;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use SplFileObject;

/**
 * Reads a price-list CSV (the format written by ExportRepairPrices) and
 * updates or adds prices, matched on model, repair and quality slugs.
 *
 * All or nothing: every row is checked first, and if any row has a problem
 * nothing is saved and the problems are reported with their line numbers.
 * Blank optional cells leave the existing value unchanged.
 */
class ImportRepairPrices
{
    private const int MAX_REPORTED_ERRORS = 20;

    private const array REQUIRED_COLUMNS = ['model_slug', 'repair_slug', 'price'];

    private const array BOOLEAN_COLUMNS = ['in_stock' => 'in_stock', 'active' => 'is_active', 'draft' => 'is_draft'];

    public function handle(string $path): PriceImportResult
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(',', '"', '');

        $header = null;
        $rows = [];
        $errors = [];

        foreach ($file as $index => $cells) {
            if (! is_array($cells) || $cells === [null]) {
                continue;
            }

            if ($header === null) {
                $header = $this->header($cells);
                $missing = array_diff(self::REQUIRED_COLUMNS, $header);

                if ($missing !== []) {
                    return new PriceImportResult(errors: ['The file is missing these columns: '.implode(', ', $missing).'. Export the price list to get a file in the right format.']);
                }

                continue;
            }

            $line = (int) $index + 1;
            $row = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''));
            $parsed = $this->parseRow($row, $line, $errors);

            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        if ($header === null) {
            return new PriceImportResult(errors: ['The file is empty.']);
        }

        if ($errors !== []) {
            return new PriceImportResult(errors: $this->limit($errors));
        }

        return DB::transaction(fn (): PriceImportResult => $this->save($rows));
    }

    /**
     * @param  array<int, string|null>  $cells
     * @return list<string>
     */
    private function header(array $cells): array
    {
        return array_map(
            fn (?string $cell): string => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell))),
            array_values($cells),
        );
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $errors
     * @return array{key: array{device_model_id: int, repair_type_id: int, part_quality_id: int|null}, values: array<string, mixed>}|null
     */
    private function parseRow(array $row, int $line, array &$errors): ?array
    {
        $models = once(fn () => DeviceModel::query()->pluck('id', 'slug'));
        $repairs = once(fn () => RepairType::query()->pluck('id', 'slug'));
        $qualities = once(fn () => PartQuality::query()->pluck('id', 'slug'));

        $modelSlug = trim((string) ($row['model_slug'] ?? ''));
        $repairSlug = trim((string) ($row['repair_slug'] ?? ''));
        $qualitySlug = trim((string) ($row['quality_slug'] ?? ''));
        $problems = [];

        if (! $models->has($modelSlug)) {
            $problems[] = "unknown model \"{$modelSlug}\"";
        }

        if (! $repairs->has($repairSlug)) {
            $problems[] = "unknown repair \"{$repairSlug}\"";
        }

        if ($qualitySlug !== '' && ! $qualities->has($qualitySlug)) {
            $problems[] = "unknown part quality \"{$qualitySlug}\"";
        }

        $values = [];
        $price = Money::parsePounds($row['price'] ?? null);

        if ($price === null) {
            $problems[] = 'price must be an amount in pounds, e.g. 59.99';
        } else {
            $values['price_pence'] = $price;
        }

        $sale = trim((string) ($row['sale_price'] ?? ''));

        if (array_key_exists('sale_price', $row)) {
            $salePence = $sale === '' ? null : Money::parsePounds($sale);

            if ($sale !== '' && $salePence === null) {
                $problems[] = 'sale price must be an amount in pounds or left empty';
            } else {
                $values['sale_price_pence'] = $salePence;
            }
        }

        $warranty = trim((string) ($row['warranty_days'] ?? ''));

        if ($warranty !== '') {
            if (! ctype_digit($warranty) || (int) $warranty > 1095) {
                $problems[] = 'warranty days must be a whole number from 0 to 1095';
            } else {
                $values['warranty_days'] = (int) $warranty;
            }
        }

        foreach (self::BOOLEAN_COLUMNS as $column => $attribute) {
            $cell = strtolower(trim((string) ($row[$column] ?? '')));

            if ($cell === '') {
                continue;
            }

            $flag = match ($cell) {
                'yes', 'y', 'true', '1' => true,
                'no', 'n', 'false', '0' => false,
                default => null,
            };

            if ($flag === null) {
                $problems[] = "{$column} must be yes or no";
            } else {
                $values[$attribute] = $flag;
            }
        }

        if ($problems !== []) {
            $errors[] = "Line {$line}: ".implode('; ', $problems).'.';

            return null;
        }

        return [
            'key' => [
                'device_model_id' => (int) $models->get($modelSlug),
                'repair_type_id' => (int) $repairs->get($repairSlug),
                'part_quality_id' => $qualitySlug === '' ? null : (int) $qualities->get($qualitySlug),
            ],
            'values' => $values,
        ];
    }

    /**
     * @param  list<array{key: array{device_model_id: int, repair_type_id: int, part_quality_id: int|null}, values: array<string, mixed>}>  $rows
     */
    private function save(array $rows): PriceImportResult
    {
        $created = $updated = $unchanged = 0;

        foreach ($rows as ['key' => $key, 'values' => $values]) {
            $price = RepairPrice::query()->where($key)->first();

            if ($price === null) {
                RepairPrice::query()->create([...$key, ...$values]);
                $created++;

                continue;
            }

            $price->fill($values);

            if ($price->isDirty()) {
                $price->save();
                $updated++;
            } else {
                $unchanged++;
            }
        }

        return new PriceImportResult($created, $updated, $unchanged);
    }

    /**
     * @param  list<string>  $errors
     * @return list<string>
     */
    private function limit(array $errors): array
    {
        if (count($errors) <= self::MAX_REPORTED_ERRORS) {
            return $errors;
        }

        $remaining = count($errors) - self::MAX_REPORTED_ERRORS;

        return [...array_slice($errors, 0, self::MAX_REPORTED_ERRORS), "…and {$remaining} more."];
    }
}
