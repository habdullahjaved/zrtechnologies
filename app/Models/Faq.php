<?php

namespace App\Models;

use App\Enums\FaqTopic;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A question and answer. Answers are plain text; blank lines become paragraphs.
 *
 * @property FaqTopic $topic
 */
#[Fillable(['topic', 'question', 'answer', 'show_on_faq_page', 'is_active', 'sort'])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'topic' => FaqTopic::class,
            'show_on_faq_page' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return MorphToMany<Service, $this>
     */
    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'faqable');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    /**
     * The answer as paragraphs, for display and structured data.
     *
     * @return list<string>
     */
    public function answerParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', $this->answer) ?: [])));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['topic', 'question', 'answer', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }
}
