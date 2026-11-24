<?php

namespace App\Models\Concerns;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * FAQs shown on this record's page (and in its FAQPage structured data).
 */
trait HasFaqs
{
    /**
     * @return MorphToMany<Faq, $this>
     */
    public function faqs(): MorphToMany
    {
        return $this->morphToMany(Faq::class, 'faqable')
            ->withPivot('sort')
            ->orderByPivot('sort');
    }
}
