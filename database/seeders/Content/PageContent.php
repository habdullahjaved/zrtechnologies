<?php

namespace Database\Seeders\Content;

use App\Enums\SystemPage;
use App\Settings\BusinessSettings;

/**
 * Content blocks for system pages: the iPhone repair pillar page, about,
 * warranty and the legal drafts. Legal pages are drafts for the owner or a
 * legal adviser to review; they are flagged "Needs legal review" in the admin.
 *
 * @phpstan-import-type Block from Blocks
 */
final class PageContent
{
    /**
     * @return array<string, list<Block>> system page key => blocks
     */
    public static function all(BusinessSettings $business): array
    {
        return [
            SystemPage::IphoneRepair->value => self::iphoneRepair(),
            SystemPage::About->value => self::about($business),
            SystemPage::Warranty->value => self::warranty(),
            SystemPage::Privacy->value => self::privacy($business),
            SystemPage::Cookies->value => self::cookies($business),
            SystemPage::Terms->value => self::terms($business),
            SystemPage::RepairTerms->value => self::repairTerms($business),
            SystemPage::Accessibility->value => self::accessibility($business),
        ];
    }

    /**
     * FAQs (by FaqContent key) shown on each page.
     *
     * @return array<string, list<string>>
     */
    public static function faqs(): array
    {
        return [
            SystemPage::Home->value => ['how-collection-works', 'where-collect', 'when-pay', 'how-long', 'passcode', 'warranty-length'],
            SystemPage::IphoneRepair->value => ['how-collection-works', 'which-parts', 'how-long', 'price-change', 'warranty-length', 'data-safe'],
            SystemPage::HowItWorks->value => ['how-soon', 'need-to-be-home', 'collect-from-work', 'prepare-iphone', 'when-pay', 'collection-fee'],
            SystemPage::Warranty->value => ['warranty-claim', 'warranty-exclusions'],
            SystemPage::About->value => ['where-collect', 'which-parts'],
        ];
    }

    /**
     * @return list<Block>
     */
    private static function iphoneRepair(): array
    {
        return [
            Blocks::h2('iPhone repair in Blackburn, without leaving home'),
            Blocks::text('<p>Most iPhone repairs mean a trip into town, a queue at the counter and another trip to collect your phone. We do it differently. Book online in a couple of minutes, choose a collection window, and we collect your iPhone from your home or workplace in Blackburn, Darwen, Accrington, Great Harwood, Clitheroe or the villages around them. We repair it, test it and bring it back.</p><p>You see the price before you book and there is nothing to pay until your iPhone is back in your hand.</p>'),
            Blocks::h2('Repairs for every iPhone from the iPhone 8 onwards'),
            Blocks::text('<p>We repair every model from the iPhone 8 and SE (2nd generation) to the latest line-up, including Pro, Pro Max, Plus, mini and Air models. The most common repairs are screen replacements, new batteries and charging ports, followed by back glass, cameras, speakers and water damage.</p><p>Each model has its own page with every repair and its price, so you can check exactly what your repair will cost.</p>'),
            Blocks::h2('Quality parts, tested twice'),
            Blocks::text('<p>We use quality-tested parts and, for many screens, let you choose between a Standard and a Premium part. Every part is tested before it is fitted, and every phone is tested again after the repair: touch, display, cameras, speakers, microphones, charging, Wi-Fi and Face ID or Touch ID.</p>'),
            Blocks::h2('Clear prices and no surprises'),
            Blocks::text('<p>Our prices include parts and labour. If diagnosis shows a second fault, we stop and ask you before doing anything else, and we never carry out extra work without your agreement. Every repair is covered by our warranty on the part and our workmanship.</p>'),
            Blocks::callout('info', 'An independent repairer', 'ZR Technologies is an independent repair provider and is not affiliated with or endorsed by Apple Inc. A third-party repair may affect any remaining Apple warranty, so check your coverage first if your iPhone is under a year old or has AppleCare+.'),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function about(BusinessSettings $business): array
    {
        return [
            Blocks::h2('Repairs that fit around your day'),
            Blocks::text("<p>{$business->trading_name} started with a simple idea: getting an iPhone fixed should not mean losing half a day. We are a Blackburn company, and instead of asking you to come to a shop, we come to you. We collect your phone from home or work, repair it with care and bring it back.</p>"),
            Blocks::h2('Who we are'),
            Blocks::text("<p>{$business->legal_name} is a limited company registered in {$business->registered_in} (company number {$business->company_number}), run by director {$business->director_name}. We are a small, local team, so you deal with the same people from booking to return.</p>"),
            Blocks::h2('How we work'),
            Blocks::text('<ul><li><strong>Clear prices first.</strong> You see what your repair costs before you book, and we ask before doing anything that changes the price.</li><li><strong>Careful with your phone and your data.</strong> We never ask for your passcode in a form and your iPhone stays with our team from collection to return.</li><li><strong>Tested twice.</strong> Every part is checked before it goes in and every phone is tested again before it comes back.</li><li><strong>Honest advice.</strong> If a repair is not worth doing, we will tell you.</li></ul>'),
            Blocks::h2('Where we are heading'),
            Blocks::text("<p>Our tagline is “{$business->tagline}”. iPhone repair is where we start. Phone accessories, custom printing and web and IT services for local businesses are on the way, built on the same promise: good work, clear prices and real people you can talk to.</p>"),
            Blocks::cta('Ready to get your iPhone fixed?', 'Book online in a couple of minutes, or call or WhatsApp us.', 'Book a repair'),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function warranty(): array
    {
        return [
            Blocks::h2('What our warranty covers'),
            Blocks::text('<p>Every repair is covered by our warranty on the part we fitted and the work we did. If the part fails or our work is at fault during the warranty period, we will collect your iPhone and put it right free of charge.</p>'),
            Blocks::table('Typical warranty periods', 'Repair | Warranty', [
                'Screen replacement (Standard part) | 6 months',
                'Screen replacement (Premium part) | 12 months',
                'Battery replacement | 12 months',
                'Charging port, back glass, camera, speaker and microphone repairs | 6 months',
                'Water damage repair | 3 months on the parts we replace',
            ]),
            Blocks::text('<p>The exact warranty for your repair is shown next to the price when you book and in your confirmation email.</p>'),
            Blocks::h2('What is not covered'),
            Blocks::text('<ul><li>new accidental damage after the repair, such as a fresh crack, a bent frame or liquid damage</li><li>damage caused by someone else opening or repairing the phone after us</li><li>faults in parts we did not replace (we are always happy to check them)</li><li>software problems, data loss and issues caused by iOS updates</li><li>normal wear, such as a battery gradually losing capacity over time</li></ul>'),
            Blocks::h2('How to make a claim'),
            Blocks::steps([
                ['Contact us', 'Call, WhatsApp or email us with your booking reference (it starts ZR-) and tell us what is wrong.'],
                ['We arrange a collection', 'We collect your iPhone at a time that suits you and check the fault.'],
                ['We put it right', 'If the warranty covers it, we repair or replace the part free of charge and return your phone.'],
            ]),
            Blocks::callout('info', 'Your legal rights', 'Our warranty is in addition to your rights under the Consumer Rights Act 2015, which require our work to be carried out with reasonable care and skill and the parts we fit to be of satisfactory quality.'),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function privacy(BusinessSettings $business): array
    {
        $office = e($business->registeredOfficeOneLine());

        return [
            Blocks::h2('Who we are'),
            Blocks::text("<p>{$business->legal_name} (“we”, “us”) is the data controller for the personal data described in this policy. We are registered in {$business->registered_in}, company number {$business->company_number}, and our registered office is {$office}.</p><p>If you have any questions about this policy or your data, email <a href=\"mailto:{$business->email}\">{$business->email}</a>.</p>"),
            Blocks::h2('The data we collect'),
            Blocks::text('<ul><li><strong>Contact details</strong>: your name, mobile number, email address and the addresses where we collect and return your iPhone.</li><li><strong>Repair details</strong>: your iPhone model, colour, the fault, the repairs we carry out, the price, and your IMEI or serial number if we record it (stored encrypted).</li><li><strong>Messages</strong>: what you tell us in forms, emails, calls and WhatsApp messages.</li><li><strong>How you found us</strong>: the page you arrived on, the website or advert that sent you, and campaign tags in the link, so we know which advertising works.</li><li><strong>Technical data</strong>: your browser and device type, and a one-way scrambled (hashed) version of your IP address, used to prevent spam and fraud.</li></ul><p>We never ask for your iPhone passcode in our forms and we do not store card details.</p>'),
            Blocks::h2('How and why we use it'),
            Blocks::table('Our purposes and lawful bases', 'Purpose | Lawful basis', [
                'Booking, collecting, repairing and returning your iPhone | Contract',
                'Sending booking confirmations and repair status updates | Contract',
                'Replying to quotes, callbacks and enquiries | Legitimate interests (responding to you), or steps before a contract',
                'Following up an unfinished booking once you have given your phone number or email | Legitimate interests (helping you complete your booking)',
                'Keeping accounts and records required by law | Legal obligation',
                'Preventing spam, fraud and misuse of our website | Legitimate interests',
                'Understanding which marketing brings customers | Legitimate interests, and consent where cookies are involved',
                'Sending offers and news by email | Consent (you can withdraw it at any time)',
            ]),
            Blocks::h2('Who we share it with'),
            Blocks::text('<p>We only share your data with organisations that help us run our service, under contracts that protect it:</p><ul><li>our website hosting and email providers</li><li>postcodes.io, which checks your postcode (we send only the postcode)</li><li>messaging services you choose to contact us with, such as WhatsApp</li><li>advertising and analytics providers (such as Google and Meta), only where you have agreed to the relevant cookies</li><li>our accountants, and authorities when the law requires it</li></ul><p>We never sell your personal data.</p>'),
            Blocks::h2('How long we keep it'),
            Blocks::text('<ul><li>repair and booking records: 6 years after the repair, for accounting and in case of a warranty or legal claim</li><li>enquiries that did not become a booking: 2 years</li><li>unfinished bookings: 12 months</li><li>marketing consent: until you withdraw it</li></ul>'),
            Blocks::h2('Your rights'),
            Blocks::text('<p>You have the right to ask for a copy of your data, to have it corrected or deleted, to restrict or object to how we use it, to have it transferred to you or another organisation, and to withdraw consent at any time. To use any of these rights, email us; we will reply within one month.</p><p>If you are unhappy with how we have handled your data, please tell us first. You also have the right to complain to the Information Commissioner’s Office (ICO) at <a href="https://ico.org.uk/make-a-complaint/">ico.org.uk</a> or on 0303 123 1113.</p>'),
            Blocks::h2('Keeping your data safe'),
            Blocks::text('<p>Our website uses an encrypted connection (HTTPS). Staff accounts are protected by multi-factor authentication, access is limited to the people who need it, and sensitive details such as IMEI numbers are stored encrypted.</p>'),
            Blocks::h2('Changes to this policy'),
            Blocks::text('<p>We will update this page if the way we use your data changes. The date at the top shows when it was last updated.</p>'),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function cookies(BusinessSettings $business): array
    {
        return [
            Blocks::h2('What cookies are'),
            Blocks::text('<p>Cookies are small text files a website stores on your device. Some are essential for the site to work; others help a business understand how its site is used or measure its advertising.</p>'),
            Blocks::h2('Cookies we use'),
            Blocks::table('Strictly necessary cookies', 'Cookie | Purpose | How long', [
                'zr_technologies_session | Keeps your booking and enquiry forms working as you move between pages | Until you close your browser or 2 hours of inactivity',
                'XSRF-TOKEN | Security: protects our forms against cross-site request forgery | Same as the session',
            ]),
            Blocks::text('<p>These cookies are essential, so they do not need your consent.</p>'),
            Blocks::h2('Analytics and marketing cookies'),
            Blocks::text('<p>We do not set analytics or marketing cookies unless you agree to them. When we add them (for example Google Analytics or the Meta Pixel), we will ask for your consent first with a choice to accept or reject, list each cookie here, and let you change your mind at any time.</p>'),
            Blocks::h2('Embedded content'),
            Blocks::text('<p>Our maps (OpenStreetMap) and videos (YouTube) only load when you choose to open them. YouTube videos use YouTube’s privacy-enhanced mode. When you open them, those services may set their own cookies under their own policies.</p>'),
            Blocks::h2('Managing cookies'),
            Blocks::text("<p>You can delete or block cookies in your browser settings. Blocking strictly necessary cookies will stop our booking and enquiry forms from working. Questions? Email <a href=\"mailto:{$business->email}\">{$business->email}</a>.</p>"),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function terms(BusinessSettings $business): array
    {
        $office = e($business->registeredOfficeOneLine());

        return [
            Blocks::h2('About us'),
            Blocks::text("<p>This website is run by {$business->legal_name}, trading as {$business->trading_name}, a company registered in {$business->registered_in} (number {$business->company_number}), registered office {$office}. Contact us at <a href=\"mailto:{$business->email}\">{$business->email}</a>.</p>"),
            Blocks::h2('Using this website'),
            Blocks::text('<p>You may use this website to find out about our services, check prices and book repairs. Please do not misuse it, for example by submitting false bookings, attempting to break its security or copying its content for commercial use.</p><p>We work to keep the information here accurate, but prices and availability can change. The price confirmed in your booking is the one that applies.</p>'),
            Blocks::h2('Our services'),
            Blocks::text('<p>Repairs are provided under our <a href="/repair-terms">repair terms</a>, which explain diagnosis, quotes, collection and return, your data, warranty and your cancellation rights. Please read them before you book.</p>'),
            Blocks::h2('Links and trademarks'),
            Blocks::text('<p>Links to other websites are provided for convenience; we are not responsible for their content. iPhone is a trademark of Apple Inc. We are an independent repair provider and are not affiliated with or endorsed by Apple.</p>'),
            Blocks::h2('Liability'),
            Blocks::text('<p>Nothing in these terms limits our liability for death or personal injury caused by our negligence, for fraud, or for anything else that cannot be limited by law. Your rights under the Consumer Rights Act 2015 are not affected.</p>'),
            Blocks::h2('Law'),
            Blocks::text('<p>These terms are governed by the law of England and Wales, and the courts of England and Wales have jurisdiction.</p>'),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function repairTerms(BusinessSettings $business): array
    {
        return [
            Blocks::h2('1. Booking and prices'),
            Blocks::text('<p>The price shown when you book covers the repair you chose, including parts and labour, and any collection fee for your area. Prices are in pounds sterling. If you choose "Not sure what’s wrong?", we charge the diagnostic fee shown when you book.</p>'),
            Blocks::h2('2. Diagnosis and changes to the price'),
            Blocks::text('<p>We check your iPhone before repairing it. If we find a fault that changes the work or the price, we will contact you with a revised quote. We will not carry out extra work without your agreement. If you decline, we return your iPhone and you pay only for any work you agreed to.</p>'),
            Blocks::h2('3. Collection and return'),
            Blocks::text('<p>Someone aged 18 or over must be present to hand over and receive the phone. We note the condition of your iPhone at collection. If we cannot reach you to return the phone, we will hold it safely and keep trying to contact you.</p>'),
            Blocks::h2('4. Your data, passcode and Activation Lock'),
            Blocks::text('<p>Please back up your iPhone before we collect it. Most repairs do not affect your data, but a phone with a fault can fail at any time, and we cannot be responsible for data lost as a result of an existing fault. We never ask for your passcode in a form. Keep Find My (Activation Lock) switched on unless we ask you to switch it off for a particular repair; we will explain why if we do.</p>'),
            Blocks::h2('5. Parts'),
            Blocks::text('<p>We use quality-tested replacement parts. On some models iOS may show a message that a part is not genuine after any third-party repair; this does not affect how the part works. A third-party repair may affect any remaining Apple warranty or AppleCare+ cover.</p>'),
            Blocks::h2('6. Your right to cancel'),
            Blocks::text('<p>Because you book online, you have the right to cancel within 14 days of booking under the Consumer Contracts Regulations 2013, without giving a reason. To cancel, contact us by phone, WhatsApp or email.</p><p>If you ask us to start the repair within those 14 days (you can tick a box to request this when you book), and then cancel, you will pay for any work already done. Once the repair has been fully completed at your request, you can no longer cancel it.</p>'),
            Blocks::h2('7. Payment'),
            Blocks::text('<p>You pay when your iPhone is returned to you, unless we have agreed otherwise in writing (for example invoice terms for a business).</p>'),
            Blocks::h2('8. Warranty'),
            Blocks::text('<p>Every repair has a warranty on the part fitted and our workmanship, for the period shown at booking. Our <a href="/warranty">warranty page</a> explains what is covered. The warranty is in addition to your legal rights.</p>'),
            Blocks::h2('9. Uncollected or unpaid devices'),
            Blocks::text('<p>If we are unable to return your iPhone or you do not pay for agreed work, we will contact you several times by phone, email and post. If we hear nothing for 90 days after our first attempt, we may dispose of the device as permitted by law, after giving you written notice.</p>'),
            Blocks::h2('10. Our responsibility to you'),
            Blocks::text("<p>We are responsible for loss or damage that is a foreseeable result of our breaking these terms or failing to use reasonable care and skill. We are not responsible for loss caused by an existing fault, by liquid damage that was already present, or by events outside our control. Nothing here limits our liability where the law does not allow it. Questions or complaints: <a href=\"mailto:{$business->email}\">{$business->email}</a>.</p>"),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function accessibility(BusinessSettings $business): array
    {
        return [
            Blocks::h2('Our commitment'),
            Blocks::text("<p>{$business->trading_name} wants everyone to be able to use this website, book a repair and get in touch. We aim to meet the Web Content Accessibility Guidelines (WCAG) 2.2 at level AA.</p>"),
            Blocks::h2('What we have done'),
            Blocks::text('<ul><li>text and buttons meet AA colour-contrast levels</li><li>every page can be used with a keyboard, with a visible focus outline and a "Skip to main content" link</li><li>pages use proper headings, labels and landmarks for screen readers</li><li>forms explain errors in words, not just colour</li><li>animations are reduced if you switch on "reduce motion" on your device</li><li>you can zoom pages up to 400% without losing content</li></ul>'),
            Blocks::h2('Known limitations'),
            Blocks::text('<p>Maps and videos come from other services (OpenStreetMap and YouTube) and may not be fully accessible. Some older photos may not yet have full descriptions. If anything stops you using the site, there is always another way: call, WhatsApp or email us and we will help you book.</p>'),
            Blocks::h2('Contact us'),
            Blocks::text("<p>If you find a problem or need information in another format, email <a href=\"mailto:{$business->email}\">{$business->email}</a>. We aim to reply within five working days.</p>"),
        ];
    }
}
