<?php

namespace Database\Seeders\Content;

/**
 * Blog categories (brief §10) and six draft cornerstone posts. The posts are
 * outlines for the owner to finish: each section lists what to cover. They are
 * seeded as drafts and never published automatically.
 *
 * @phpstan-import-type Block from Blocks
 */
final class BlogContent
{
    /**
     * @return list<array{name: string, slug: string, description: string}>
     */
    public static function categories(): array
    {
        return [
            ['name' => 'iPhone Care & Protection', 'slug' => 'iphone-care-and-protection', 'description' => 'Simple ways to keep your iPhone in good shape, from cases and screen protectors to cleaning and storage.'],
            ['name' => 'Battery Health', 'slug' => 'battery-health', 'description' => 'How iPhone batteries age, what Battery Health really means and how to make yours last longer.'],
            ['name' => 'Screen & Glass', 'slug' => 'screen-and-glass', 'description' => 'Cracked screens, back glass and protectors: what to do, what it costs and how to avoid it.'],
            ['name' => 'Water Damage', 'slug' => 'water-damage', 'description' => 'What to do when your iPhone gets wet, what water resistance really means, and when a repair can help.'],
            ['name' => 'Buying Guides', 'slug' => 'buying-guides', 'description' => 'Honest advice on cases, screen protectors, chargers and cables that are worth your money.'],
            ['name' => 'Troubleshooting', 'slug' => 'troubleshooting', 'description' => 'Step-by-step fixes for common iPhone problems you can try before booking a repair.'],
            ['name' => 'Security & Privacy', 'slug' => 'security-and-privacy', 'description' => 'Protecting your data, passcodes, backups and Find My, including before and after a repair.'],
            ['name' => 'News & Updates', 'slug' => 'news-and-updates', 'description' => 'News from ZR Technologies and the iPhone world that matters to our customers.'],
        ];
    }

    /**
     * @return list<array{title: string, slug: string, category: string, service: string|null, excerpt: string, blocks: list<Block>}>
     */
    public static function posts(): array
    {
        return [
            [
                'title' => 'How to protect your iPhone screen: tempered glass vs film',
                'slug' => 'tempered-glass-vs-film-screen-protector',
                'category' => 'screen-and-glass',
                'service' => 'screen-replacement',
                'excerpt' => 'Tempered glass or plastic film? We compare protection, feel and price, and explain how to fit a screen protector without bubbles.',
                'blocks' => self::outline([
                    'Why screen protectors are worth it' => ['the cost of a screen replacement compared with a protector', 'what a protector can and cannot stop (scratches, drops, edge impacts)'],
                    'Tempered glass: pros and cons' => ['feel and clarity', 'how it absorbs impacts and cracks instead of the screen', '9H hardness claims explained'],
                    'Film protectors: pros and cons' => ['thin, flexible and cheap', 'scratch protection but little drop protection'],
                    'Comparison table' => ['add a table block: protection, feel, price, Face ID and case compatibility'],
                    'How to fit one without bubbles' => ['clean, dust-free room and a lint-free cloth', 'line up with the speaker and camera cut-outs', 'press from the centre outwards'],
                    'When a cracked protector means a cracked screen' => ['how to tell the difference', 'link to the screen replacement page'],
                ]),
            ],
            [
                'title' => 'iPhone battery health explained: when to replace it',
                'slug' => 'iphone-battery-health-explained',
                'category' => 'battery-health',
                'service' => 'battery-replacement',
                'excerpt' => 'What the Battery Health percentage means, why iPhones slow down as batteries age, and the signs it is time for a new battery.',
                'blocks' => self::outline([
                    'Where to find Battery Health' => ['Settings → Battery → Battery Health & Charging', 'what maximum capacity and peak performance capability mean'],
                    'Why batteries wear out' => ['charge cycles and chemical ageing', 'heat as the biggest enemy'],
                    'Why a worn battery can slow your iPhone' => ['performance management explained in plain English'],
                    'Signs it is time to replace it' => ['below about 80% capacity or "Service" shown', 'unexpected shutdowns', 'a swollen battery lifting the screen (safety warning)'],
                    'What a battery replacement involves' => ['how long it takes', 'the "not genuine" message on newer models', 'link to the battery replacement page'],
                ]),
            ],
            [
                'title' => 'Dropped your iPhone in water? What to do in the first hour',
                'slug' => 'iphone-water-damage-first-hour',
                'category' => 'water-damage',
                'service' => 'water-damage-repair',
                'excerpt' => 'The first hour after your iPhone gets wet matters most. Here is exactly what to do, what not to do, and why rice will not help.',
                'blocks' => self::outline([
                    'Do this straight away' => ['switch it off and do not charge it', 'remove the case and SIM tray', 'wipe it dry and let it rest port-down'],
                    'What not to do' => ['why rice does not work', 'no hairdryers, ovens or radiators', 'do not keep testing it'],
                    'Water resistance is not waterproof' => ['IP ratings explained', 'why resistance fades with age and after drops'],
                    'Salt water, pool water and drinks' => ['why they cause faster corrosion than fresh water'],
                    'When to get it checked' => ['why corrosion can appear days later', 'saving your data first', 'link to the water damage repair page'],
                ]),
            ],
            [
                'title' => 'Original vs aftermarket iPhone screens: what UK buyers should know',
                'slug' => 'original-vs-aftermarket-iphone-screens',
                'category' => 'screen-and-glass',
                'service' => 'screen-replacement',
                'excerpt' => 'Not all replacement screens are equal. We explain the difference between screen grades, what affects colour and touch, and how to choose.',
                'blocks' => self::outline([
                    'The main types of replacement screen' => ['OLED and LCD explained', 'what "Standard" and "Premium" mean at ZR Technologies'],
                    'What you will notice' => ['colour, brightness and viewing angles', 'touch response and battery use'],
                    'True Tone and Face ID after a screen repair' => ['what is kept from your original screen', 'the "unknown part" message on newer models'],
                    'How to choose' => ['how long you plan to keep the phone', 'budget versus best quality'],
                    'Questions to ask any repairer' => ['warranty length', 'testing after the repair', 'link to our screen replacement prices'],
                ]),
            ],
            [
                'title' => 'How to back up your iPhone before a repair',
                'slug' => 'how-to-back-up-iphone-before-repair',
                'category' => 'security-and-privacy',
                'service' => null,
                'excerpt' => 'A step-by-step guide to backing up your iPhone with iCloud or a computer before any repair, and checking the backup worked.',
                'blocks' => self::outline([
                    'Why back up before a repair' => ['most repairs do not touch your data', 'but a faulty phone can fail at any time'],
                    'Back up with iCloud' => ['Settings → your name → iCloud → iCloud Backup', 'check you have enough iCloud storage', 'how to confirm the backup finished'],
                    'Back up with a Mac or PC' => ['Finder on a Mac, Apple Devices or iTunes on Windows', 'encrypting the backup to keep passwords and health data'],
                    'What about photos and WhatsApp?' => ['iCloud Photos', 'WhatsApp’s own chat backup'],
                    'Passcodes and Find My' => ['why we never ask for your passcode in a form', 'keep Find My switched on unless asked'],
                ]),
            ],
            [
                'title' => 'Best ways to make your iPhone battery last longer',
                'slug' => 'make-iphone-battery-last-longer',
                'category' => 'battery-health',
                'service' => 'battery-replacement',
                'excerpt' => 'Practical settings and habits that make your iPhone battery last longer each day and age more slowly over the years.',
                'blocks' => self::outline([
                    'Settings that help straight away' => ['Low Power Mode', 'screen brightness and Auto-Lock', 'background app refresh and location services'],
                    'Charging habits that slow battery ageing' => ['Optimised Battery Charging and the 80% limit', 'avoiding heat while charging', 'using good-quality chargers and cables'],
                    'Find the apps draining your battery' => ['Settings → Battery usage by app'],
                    'Myths to ignore' => ['closing all your apps', 'letting the battery run flat'],
                    'When habits are not enough' => ['signs the battery is worn', 'link to the battery replacement page'],
                ]),
            ],
        ];
    }

    /**
     * An outline post: a "to finish" note, then each section heading with the
     * points it should cover.
     *
     * @param  array<string, list<string>>  $sections
     * @return list<Block>
     */
    private static function outline(array $sections): array
    {
        $blocks = [
            Blocks::callout('warning', 'Draft outline: to be written by the owner', 'Replace the bullet points in each section with your own words and experience, add photos, then set the post to "Ready for review". Remove this box before publishing.'),
        ];

        foreach ($sections as $heading => $points) {
            $blocks[] = Blocks::h2($heading);
            $blocks[] = Blocks::text('<ul>'.implode('', array_map(fn (string $point): string => '<li>'.e($point).'</li>', $points)).'</ul>');
        }

        $blocks[] = Blocks::cta();

        return $blocks;
    }
}
