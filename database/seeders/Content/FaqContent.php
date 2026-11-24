<?php

namespace Database\Seeders\Content;

use App\Enums\FaqTopic;

/**
 * The starting FAQs for /faqs and the pages they are attached to. Answers
 * avoid promises the owner has not confirmed (exact times, payment methods
 * are taken from settings wording); edit them in Content → FAQs.
 */
final class FaqContent
{
    /**
     * key => [topic, question, answer]. Keys let other seed content attach FAQs.
     *
     * @return array<string, array{0: FaqTopic, 1: string, 2: string}>
     */
    public static function all(): array
    {
        return [
            // Booking, collection and return.
            'how-collection-works' => [FaqTopic::Booking, 'How does your collection and return service work?',
                "Book online, choose a collection window and we come to your home or workplace to collect your iPhone. We repair it, test it and bring it back to you.\n\nYou do not need to travel anywhere or wait around in a shop."],
            'where-collect' => [FaqTopic::Booking, 'Which areas do you collect from?',
                "We collect across Blackburn, Darwen, Accrington and Oswaldtwistle, Great Harwood and Clitheroe, plus the surrounding villages.\n\nEnter your postcode on our Areas We Cover page for an instant answer. If you are just outside our area, get in touch and we will see what we can do."],
            'how-soon' => [FaqTopic::Booking, 'How soon can you collect my iPhone?',
                'You can choose from the collection windows shown when you book. Same-day collection is often available if you book early in the day, and Saturday collections are available too.'],
            'need-to-be-home' => [FaqTopic::Booking, 'Do I need to be in when you collect and return my iPhone?',
                "Yes, or someone you trust needs to be there. We hand your iPhone over in person and check its condition with you, so it is never left unattended.\n\nIf your plans change, call or message us and we will move the collection to another window."],
            'collect-from-work' => [FaqTopic::Booking, 'Can you collect from my workplace instead of my home?',
                'Yes. When you book, enter the address where you will be at the collection time. You can also ask us to return the phone to a different address.'],
            'prepare-iphone' => [FaqTopic::Booking, 'How should I prepare my iPhone before you collect it?',
                "Back it up to iCloud or a computer, remove your case and screen protector if you can, and make sure the battery has some charge if the phone still turns on.\n\nKeep Find My switched on unless we ask you to switch it off for a particular repair."],

            // Repairs and parts.
            'how-long' => [FaqTopic::Repairs, 'How long does a repair take?',
                "Most screen and battery repairs take under two hours once we have your iPhone. Charging port and camera repairs are similar.\n\nWater damage and data recovery take longer because the phone needs to be cleaned and assessed first. We always tell you what to expect."],
            'which-parts' => [FaqTopic::Repairs, 'What kind of parts do you use?',
                "We use quality-tested replacement parts and, for screens on many models, offer a choice of Standard and Premium. Premium is our top-grade part, closest to the original for colour, brightness and touch.\n\nEvery part is tested before it is fitted and again after the repair."],
            'genuine-message' => [FaqTopic::Repairs, 'Will I see a “non-genuine part” message after the repair?',
                "On newer iPhones, iOS may show a message in Settings saying a part is unknown or not genuine after a third-party repair. It does not affect how the part works.\n\nWe will tell you before the repair if your model is likely to show this message."],
            'face-id-after-screen' => [FaqTopic::Repairs, 'Will Face ID still work after a screen replacement?',
                'Yes. Face ID uses sensors that stay in your phone during a screen replacement, so they keep working. If Face ID was already damaged before the repair, we will tell you during diagnosis.'],
            'not-sure-fault' => [FaqTopic::Repairs, 'What if I don’t know what is wrong with my iPhone?',
                'Choose "Not sure what’s wrong?" when you book. We test every function, find the fault and give you a clear price before any repair starts. You can decide not to go ahead.'],
            'water-damage-what-to-do' => [FaqTopic::Repairs, 'My iPhone got wet. What should I do?',
                "Switch it off straight away and do not charge it. Do not put it in rice: it does not dry the inside and can leave starch in the ports.\n\nBook a water damage assessment as soon as you can. The sooner we can clean the board, the better the chance of saving the phone and your data."],

            // Prices and payment.
            'when-pay' => [FaqTopic::Prices, 'When do I pay?',
                'There is nothing to pay when you book. You pay when your iPhone is returned to you, once you are happy that it is working.'],
            'price-change' => [FaqTopic::Prices, 'Can the price change after you collect my iPhone?',
                "The price you see when you book is the price you pay for that repair. Sometimes diagnosis shows a second fault, such as a damaged charging port as well as a cracked screen.\n\nIf that happens we stop, explain what we found and give you a new price. We never carry out extra work without your agreement."],
            'collection-fee' => [FaqTopic::Prices, 'Do you charge for collection and return?',
                'Collection and return is free in our core areas. A small fee applies to some outer areas, and it is shown clearly before you confirm your booking.'],
            'not-repairable' => [FaqTopic::Prices, 'What happens if my iPhone can’t be repaired?',
                'We will explain why and return it to you. You do not pay for a repair we could not complete.'],

            // Warranty and aftercare.
            'warranty-length' => [FaqTopic::Warranty, 'Is my repair covered by a warranty?',
                "Yes. Every repair comes with a warranty on the part we fitted and our workmanship. The length depends on the repair and part, and is shown next to the price before you book.\n\nSee our Warranty page for what is covered."],
            'warranty-claim' => [FaqTopic::Warranty, 'What do I do if something goes wrong after the repair?',
                'Call or message us with your booking reference. If the problem is with the part we fitted or our work, we will collect your iPhone again and put it right free of charge.'],
            'warranty-exclusions' => [FaqTopic::Warranty, 'What isn’t covered by the warranty?',
                'New accidental damage after the repair, such as a fresh crack or liquid damage, is not covered, and neither is damage from someone else opening the phone. Faults in other parts that we did not replace are not covered either, but we are always happy to look at them.'],

            // Data and privacy.
            'passcode' => [FaqTopic::Data, 'Do you need my passcode?',
                "No. We never ask for your passcode in a booking form, and most repairs can be done and tested without it.\n\nFor some faults we may need to test the phone unlocked. If so, we will explain why and you can choose to unlock it in front of us at collection or return instead."],
            'data-safe' => [FaqTopic::Data, 'Is my data safe during the repair?',
                "Screen, battery and most other repairs do not affect your data. Your iPhone stays with our team from collection to return.\n\nWe still recommend backing up before any repair, because a phone with an existing fault can fail at any time."],
            'find-my' => [FaqTopic::Data, 'Do I need to turn off Find My?',
                'Usually not. Keep Find My switched on so your iPhone stays protected. If a particular repair needs it switched off, we will tell you when you book and explain why.'],

            // Business repairs.
            'business-batch' => [FaqTopic::Business, 'Can you repair several phones for my business at once?',
                'Yes. Tell us the models and faults and we will quote for the whole batch, collect them from your site and return them together, each with its own job reference.'],
            'business-invoice' => [FaqTopic::Business, 'Can my business pay by invoice?',
                'For business customers we can agree invoicing terms in advance. Use the business enquiry form and we will set this up with you.'],
        ];
    }
}
