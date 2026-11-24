<?php

namespace App\Support\Messaging;

use App\Models\MessageTemplate;
use App\Settings\BusinessSettings;
use App\Support\Phone;
use Illuminate\Support\HtmlString;

/**
 * Fills a message template's {placeholders}.
 *
 * For emails, values are HTML-escaped (customers type some of them) unless
 * passed as HtmlString. A line whose placeholders are all empty is removed,
 * so an optional {message} leaves no gap.
 */
final class MessageRenderer
{
    public function __construct(private readonly BusinessSettings $business) {}

    /**
     * The active template for a key, or the catalogue default when it is missing.
     */
    public function template(string $key): ?MessageTemplate
    {
        $template = MessageTemplate::query()->where('key', $key)->first();

        if ($template !== null) {
            return $template->is_active ? $template : null;
        }

        $default = MessageTemplateCatalogue::all()[$key] ?? null;

        return $default === null ? null : new MessageTemplate([
            'key' => $key,
            'channel' => $default['channel'],
            'name' => $default['name'],
            'subject' => $default['subject'],
            'body' => $default['body'],
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, string|int|HtmlString|null>  $variables
     */
    public function render(string $text, array $variables, bool $escape = true): string
    {
        $values = [];

        foreach ([...$this->commonVariables(), ...$variables] as $name => $value) {
            $values[$name] = match (true) {
                $value instanceof HtmlString => $value->toHtml(),
                $value === null => '',
                $escape => e((string) $value),
                default => (string) $value,
            };
        }

        $lines = preg_split('/\R/', $text) ?: [];
        $output = [];

        foreach ($lines as $line) {
            $placeholders = [];
            preg_match_all('/\{([a-z_]+)\}/', $line, $placeholders);
            $names = $placeholders[1];

            $rendered = preg_replace_callback(
                '/\{([a-z_]+)\}/',
                fn (array $match): string => $values[$match[1]] ?? '',
                $line,
            ) ?? $line;

            $allEmpty = $names !== [] && collect($names)->every(fn (string $name): bool => ($values[$name] ?? '') === '');

            if ($allEmpty && trim(preg_replace('/\{[a-z_]+\}/', '', $line) ?? '') === '') {
                continue;
            }

            $output[] = $rendered;
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $output)));
    }

    /**
     * @return array<string, string>
     */
    private function commonVariables(): array
    {
        return [
            'business_name' => $this->business->trading_name,
            'business_phone' => Phone::display($this->business->phone),
            'business_email' => $this->business->email,
            'whatsapp_url' => Phone::whatsAppUrl($this->business->whatsapp),
            'website_url' => rtrim($this->business->website_url, '/'),
        ];
    }
}
