<?php

namespace App\Console\Commands;

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Marks scheduled pages and posts as published once their date has passed.
 * They are already visible from that moment; saving them also clears the
 * page cache and keeps the admin's status lists accurate.
 */
#[Signature('content:publish-scheduled')]
#[Description('Publish scheduled pages and blog posts whose publish date has passed')]
class PublishScheduledContent extends Command
{
    public function handle(): int
    {
        $published = 0;

        foreach ([Page::class, Post::class] as $model) {
            $due = $model::query()
                ->where('status', PublishStatus::Scheduled->value)
                ->where('published_at', '<=', now())
                ->get();

            foreach ($due as $record) {
                $record->update(['status' => PublishStatus::Published]);
                $published++;
            }
        }

        $this->info("Published {$published} scheduled ".str('item')->plural($published).'.');

        return self::SUCCESS;
    }
}
