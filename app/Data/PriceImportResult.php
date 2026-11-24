<?php

namespace App\Data;

/**
 * What a price-list CSV import did, or why it was refused.
 */
final readonly class PriceImportResult
{
    /**
     * @param  list<string>  $errors  Problems found; when not empty, nothing was saved.
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $unchanged = 0,
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }

    public function summary(): string
    {
        return "{$this->updated} updated, {$this->created} added, {$this->unchanged} unchanged.";
    }
}
