<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Open 24/7: replace "within a few working hours" in the contact page
 * introduction and the "enquiry received" email, where the owner has not
 * already changed the wording.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const array REPLACEMENTS = [
        'usually within a few working hours.' => 'usually within a few hours. We are open 24/7.',
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $old => $new) {
            DB::table('pages')->where('intro', 'like', '%'.$old.'%')->get(['id', 'intro'])
                ->each(fn (object $page) => DB::table('pages')->where('id', $page->id)->update(['intro' => str_replace($old, $new, (string) $page->intro)]));

            DB::table('message_templates')->where('body', 'like', '%'.$old.'%')->get(['id', 'body'])
                ->each(fn (object $template) => DB::table('message_templates')->where('id', $template->id)->update(['body' => str_replace($old, $new, (string) $template->body)]));
        }
    }

    public function down(): void
    {
        // Wording only: nothing to undo.
    }
};
