<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Support\Messaging\MessageTemplateCatalogue;
use Illuminate\Database\Seeder;

/**
 * The wording of every customer email, team alert and WhatsApp message
 * (Settings → Message templates). Safe to run repeatedly and in production:
 * templates are only created when missing, so the owner's edits are kept.
 */
class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MessageTemplateCatalogue::all() as $key => $template) {
            MessageTemplate::query()->firstOrCreate(['key' => $key], [
                'channel' => $template['channel'],
                'name' => $template['name'],
                'description' => $template['description'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'is_active' => true,
            ]);
        }
    }
}
