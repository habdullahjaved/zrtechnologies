<?php

namespace Database\Seeders\Content;

use App\Enums\FaqTopic;

/**
 * The ten iPhone repair pages (brief §4). Each page lists the repair types it
 * covers; its price table is built from the live price list.
 *
 * @phpstan-type Block array{type: string, data: array<string, mixed>}
 * @phpstan-type ServicePage array{name: string, slug: string, summary: string, intro: string, icon: string, og_image: string, repairs: list<string>, blocks: list<Block>, faqs: list<string>, own_faqs: list<array{0: string, 1: string}>}
 */
final class ServicePageContent
{
    /**
     * @return list<ServicePage>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'Screen Replacement',
                'slug' => 'screen-replacement',
                'summary' => 'Cracked glass, lines, black spots or a touchscreen that will not respond. We fit a new display and test touch, True Tone and Face ID before return.',
                'intro' => 'A cracked or faulty screen fixed without leaving home. We collect your iPhone, fit a new display, test everything and bring it back, usually the same day.',
                'icon' => 'screen',
                'og_image' => 'images/og/og-screen-replacement.jpg',
                'repairs' => ['screen-replacement'],
                'blocks' => [
                    Blocks::h2('Signs your iPhone needs a new screen'),
                    Blocks::text('<p>A screen does not have to be shattered to need replacing. Book a screen replacement if you notice any of these:</p><ul><li>cracked or chipped glass, even a small crack near the edge</li><li>lines, flickering, green or pink tints across the display</li><li>black spots or patches that spread over time</li><li>parts of the screen that do not respond to touch, or "ghost" touches</li><li>a display that stays black although the phone rings or vibrates</li></ul><p>Cracks let in dust and moisture and often spread, so a small crack today can become a dead display next month. Replacing the screen early also protects your fingers from sharp glass.</p>'),
                    Blocks::h2('Standard or Premium: which screen is right for you?'),
                    Blocks::text('<p>For most models we offer two grades of screen, and you choose when you book:</p><ul><li><strong>Standard</strong> is a quality replacement that we test fully before it leaves us. It is the best-value way to get a clear, responsive screen again.</li><li><strong>Premium</strong> is our top-grade part, closest to the original for colour accuracy, brightness and touch response, with a longer warranty.</li></ul><p>On OLED models (iPhone X and later, except the XR and 11) the difference is easiest to see in colour and brightness. If you are not sure, choose Premium for a phone you plan to keep for a long time and Standard if you want the lowest price.</p>'),
                    Blocks::h2('What happens during the repair'),
                    Blocks::steps([
                        ['We check the phone with you', 'At collection we note the condition of your iPhone and check which functions still work.'],
                        ['We fit the new display', 'Your original earpiece, Face ID or Touch ID parts and sensors stay in your phone, so they keep working.'],
                        ['We test everything', 'Touch across the whole screen, True Tone, brightness, Face ID or Touch ID, the front camera and the earpiece speaker.'],
                        ['We bring it back', 'You check the new screen, pay, and the repair is covered by our warranty.'],
                    ]),
                    Blocks::callout('tip', 'Protect your new screen', 'A tempered glass screen protector and a case with a raised edge are the cheapest insurance against another crack. Ask us when you book.'),
                    Blocks::cta('Book a screen replacement', 'Choose your model to see your exact price, then pick a collection time.'),
                ],
                'faqs' => ['face-id-after-screen', 'genuine-message', 'how-long', 'data-safe'],
                'own_faqs' => [],
            ],
            [
                'name' => 'Battery Replacement',
                'slug' => 'battery-replacement',
                'summary' => 'Draining fast, shutting down early or showing "Service" in Battery Health. A new battery brings back a full day of charge.',
                'intro' => 'Get a full day of charge back. We collect your iPhone, fit a new battery, check it is charging properly and bring it back, usually within a couple of hours of collection.',
                'icon' => 'battery',
                'og_image' => 'images/og/og-battery-replacement.jpg',
                'repairs' => ['battery-replacement'],
                'blocks' => [
                    Blocks::h2('When should you replace your iPhone battery?'),
                    Blocks::text('<p>iPhone batteries wear out with use. Most people notice the change after two or three years, but heavy use can wear a battery out sooner. Signs your battery needs replacing:</p><ul><li>the phone no longer lasts the day, or the percentage drops suddenly</li><li>it switches off in the cold or at 20–30% charge</li><li>apps feel slow because iOS is limiting performance to protect the battery</li><li>Settings → Battery → Battery Health shows <em>Service</em> or a maximum capacity below about 80%</li><li>the phone feels unusually warm, or the screen is lifting at the edge</li></ul>'),
                    Blocks::callout('warning', 'Swollen battery?', 'If your screen is lifting or the case is bulging, the battery may be swelling. Stop charging the phone, keep it out of your pocket and book a repair straight away.'),
                    Blocks::h2('What is included'),
                    Blocks::text('<p>We fit a new battery with fresh adhesive, so the phone stays sealed against dust, then test charging, check the battery reports correctly and make sure the phone powers up and shuts down properly. Your data, apps and settings are not touched.</p><p>On recent models iOS may show a message saying the battery is not genuine after any third-party battery repair. The battery works normally; we will tell you when you book if your model is likely to show this.</p>'),
                    Blocks::h2('Make your new battery last'),
                    Blocks::text('<p>Keep the phone out of direct heat, avoid leaving it at 100% on the charger for days at a time, and switch on Optimised Battery Charging in Settings. Our blog has more battery tips.</p>'),
                    Blocks::cta('Book a battery replacement', 'Choose your model for your exact price.'),
                ],
                'faqs' => ['how-long', 'genuine-message', 'when-pay'],
                'own_faqs' => [
                    ['Will a new battery make my iPhone faster?', 'Often, yes. When a battery is worn, iOS can slow the phone down to stop it shutting off unexpectedly. A new battery removes that limit, so many customers notice their phone feels quicker afterwards.'],
                ],
            ],
            [
                'name' => 'Charging Port Repair',
                'slug' => 'charging-port-repair',
                'summary' => 'Loose cable, slow charging or not charging at all. We clean the port first and only replace it if cleaning does not fix the fault.',
                'intro' => 'Cable keeps falling out or your iPhone will not charge? We collect it, find out whether the port needs cleaning or replacing, fix it and bring it back.',
                'icon' => 'charging',
                'og_image' => 'images/og/og-charging-port-repair.jpg',
                'repairs' => ['charging-port-repair'],
                'blocks' => [
                    Blocks::h2('Common charging problems'),
                    Blocks::text('<ul><li>the cable feels loose or only charges at a certain angle</li><li>charging starts and stops, or is much slower than it used to be</li><li>"Accessory not supported" messages with cables that used to work</li><li>the phone will not connect to a computer or CarPlay</li><li>no sound through wired headphones or adapters</li></ul>'),
                    Blocks::h2('Clean first, replace only if needed'),
                    Blocks::text('<p>Pocket fluff packed into the port is the most common cause of charging trouble. We always inspect and carefully clean the port first. If cleaning fixes it, there is no need for a new port and we will confirm the price with you before your iPhone comes back. If the port itself is damaged or worn, we replace it and test charging, data and audio before your iPhone comes back.</p>'),
                    Blocks::callout('tip', 'Try another cable first', 'Cables wear out too. If you have another Apple or MFi-certified cable and plug, try it before booking.'),
                    Blocks::callout('warning', 'Do not dig it out with metal', 'Pins, needles and paper clips can damage the contacts inside the port. Leave cleaning to us.'),
                    Blocks::cta('Book a charging port repair'),
                ],
                'faqs' => ['how-long', 'price-change'],
                'own_faqs' => [
                    ['Is my charging problem the port or the battery?', 'If the phone charges when the cable is held at an angle, it is usually the port. If it charges normally but drains quickly, it is usually the battery. We test both during diagnosis and tell you which one needs attention before any work starts.'],
                ],
            ],
            [
                'name' => 'Back Glass Repair',
                'slug' => 'back-glass-repair',
                'summary' => 'Shattered rear glass replaced so your iPhone is safe to hold again and protected from dust and moisture.',
                'intro' => 'Cracked the back of your iPhone? We collect it, replace the rear glass so it is smooth and safe to hold again, and bring it back.',
                'icon' => 'backglass',
                'og_image' => 'images/og/og-back-glass-repair.jpg',
                'repairs' => ['back-glass-repair'],
                'blocks' => [
                    Blocks::h2('Why fix cracked back glass?'),
                    Blocks::text('<p>Since the iPhone 8, the back of every iPhone is glass so it can charge wirelessly. Once it cracks, sharp edges can cut fingers, splinters fall out, and gaps let in dust and moisture that can damage the parts inside. A cracked back also makes it harder to sell or trade in your phone.</p>'),
                    Blocks::h2('What we replace'),
                    Blocks::text('<p>We remove the broken glass and fit a new back panel in a matching colour, then check wireless charging, the rear cameras and the flash. Repair times are longer than for a screen, because the old glass has to be removed carefully without harming the parts underneath.</p>'),
                    Blocks::callout('info', 'Camera lens cracked too?', 'The camera lens covers are separate from the back glass. If they are cracked, add a camera lens replacement when you book.'),
                    Blocks::cta('Book a back glass repair'),
                ],
                'faqs' => ['how-long', 'when-pay'],
                'own_faqs' => [
                    ['Will wireless charging still work after a back glass repair?', 'Yes. The wireless charging coil stays in your phone. We test wireless charging, along with the rear cameras and flash, before returning your iPhone.'],
                ],
            ],
            [
                'name' => 'Camera Repair',
                'slug' => 'camera-repair',
                'summary' => 'Blurry photos, a shaking lens, a black camera screen or cracked lens glass. We repair rear cameras, front cameras and camera lenses.',
                'intro' => 'Sharp photos again. We repair rear and front cameras and replace cracked camera lens covers, then test focus, stabilisation and every lens before your iPhone comes back.',
                'icon' => 'camera',
                'og_image' => 'images/og/og-camera-repair.jpg',
                'repairs' => ['rear-camera-repair', 'front-camera-repair', 'camera-lens-replacement'],
                'blocks' => [
                    Blocks::h2('Which camera repair do you need?'),
                    Blocks::table('Camera faults and the usual fix', 'Problem | Usual repair', [
                        'Photos blurry, camera will not focus | Rear camera repair',
                        'Lens shakes, buzzes or clicks | Rear camera repair',
                        'Black screen in the Camera app | Rear or front camera repair',
                        'Selfies blurry or FaceTime camera black | Front camera repair',
                        'Glass over the lens cracked, photos hazy | Camera lens replacement',
                    ]),
                    Blocks::text('<p>Not sure? Choose the closest option, or "Not sure what’s wrong?" when you book. We check every lens and tell you the exact repair and price before we start.</p>'),
                    Blocks::h2('Cracked lens glass'),
                    Blocks::text('<p>A cracked lens cover is the cheapest camera repair and worth doing quickly: cracked glass makes photos hazy and lets dust reach the camera itself. We replace the cover glass and clean the camera behind it.</p>'),
                    Blocks::callout('info', 'Shaking camera after a bike ride?', 'Apple warns that strong vibration, such as from a motorbike engine, can damage the stabilisation in iPhone cameras. If your camera buzzes or will not settle, it needs a rear camera repair.'),
                    Blocks::cta('Book a camera repair'),
                ],
                'faqs' => ['not-sure-fault', 'how-long'],
                'own_faqs' => [],
            ],
            [
                'name' => 'Water Damage Repair',
                'slug' => 'water-damage-repair',
                'summary' => 'Dropped in water? Switch it off and do not charge it. We clean and assess the board, then tell you exactly what can be repaired and the cost.',
                'intro' => 'Dropped your iPhone in water? Switch it off, do not charge it and book a collection. We clean and assess it quickly and tell you honestly what can be saved.',
                'icon' => 'water',
                'og_image' => 'images/og/og-water-damage-repair.jpg',
                'repairs' => ['water-damage-repair'],
                'blocks' => [
                    Blocks::h2('What to do straight away'),
                    Blocks::steps([
                        ['Switch it off', 'Power running through a wet board causes corrosion. Turn the phone off as soon as you can.'],
                        ['Do not charge it', 'Even if it seems to work, do not plug it in until it has been checked.'],
                        ['Skip the rice', 'Rice does not dry the inside of a phone and can leave starch and dust in the ports.'],
                        ['Book an assessment', 'The sooner we clean the board, the better the chance of saving the phone and your data.'],
                    ]),
                    Blocks::h2('How our water damage service works'),
                    Blocks::text('<p>We open the phone, dry and clean the board and connectors to stop corrosion, then test each part. Liquid damage affects every phone differently, so we tell you exactly what we found, what needs replacing and what it will cost before any further repair.</p><p>The assessment price is shown when you book. If the phone cannot be saved, we will tell you honestly and, where possible, talk you through options for your data.</p>'),
                    Blocks::callout('info', 'Water resistant is not waterproof', 'iPhone water resistance wears down over time and after any drop or repair, and Apple’s warranty does not cover liquid damage. Treat every splash as a risk.'),
                    Blocks::cta('Book a water damage assessment'),
                ],
                'faqs' => ['water-damage-what-to-do', 'not-repairable', 'data-safe'],
                'own_faqs' => [
                    ['Can you save the photos from a water-damaged iPhone?', 'Often, yes, if the phone is switched off quickly and brought to us soon. We focus on getting the phone working long enough to back it up. If you have photos that are not backed up, tell us when you book so we can prioritise your data.'],
                ],
            ],
            [
                'name' => 'Speaker & Microphone Repair',
                'slug' => 'speaker-microphone-repair',
                'summary' => 'Quiet or crackly calls, muffled music, or people cannot hear you on calls.',
                'intro' => 'Crackly calls, muffled music or people struggling to hear you? We collect your iPhone, find the faulty speaker or microphone, repair it and bring it back.',
                'icon' => 'speaker',
                'og_image' => 'images/og/og-iphone-repair.jpg',
                'repairs' => ['speaker-microphone-repair'],
                'blocks' => [
                    Blocks::h2('Speaker and microphone faults we fix'),
                    Blocks::text('<ul><li>the earpiece is quiet or crackly when you hold the phone to your ear</li><li>music, videos and speakerphone sound muffled or distorted</li><li>people cannot hear you on calls, or your voice notes are silent</li><li>Siri does not respond to your voice</li></ul>'),
                    Blocks::h2('Check these first'),
                    Blocks::text('<p>Remove your case and screen protector and check the speaker grilles for fluff. Make sure Bluetooth is off, so sound is not going to headphones, and restart the phone. If the problem stays, book a repair and we will find out which part is at fault.</p>'),
                    Blocks::callout('tip', 'Gentle cleaning only', 'A soft, dry toothbrush can lift fluff from the grilles. Never push anything sharp into them: the mesh is easily damaged.'),
                    Blocks::cta('Book a speaker or microphone repair'),
                ],
                'faqs' => ['not-sure-fault', 'how-long'],
                'own_faqs' => [],
            ],
            [
                'name' => 'Face ID Repair',
                'slug' => 'face-id-repair',
                'summary' => '"Face ID is not available" after a drop or repair. We diagnose the fault first and explain honestly whether it can be repaired.',
                'intro' => '"Face ID is not available"? We collect your iPhone, diagnose the fault and tell you honestly whether it can be repaired and what it will cost before any work starts.',
                'icon' => 'faceid',
                'og_image' => 'images/og/og-iphone-repair.jpg',
                'repairs' => ['face-id-repair'],
                'blocks' => [
                    Blocks::h2('Why Face ID stops working'),
                    Blocks::text('<p>Face ID uses a set of sensors at the top of the screen, including a dot projector and infrared camera. A drop, liquid damage or an earlier repair can damage them. You may see "Face ID is not available", or the set-up screen may ask you to move your iPhone higher or lower and never finish.</p>'),
                    Blocks::h2('Diagnosis first, honest advice'),
                    Blocks::text('<p>The Face ID parts are paired to your iPhone, so not every fault can be repaired. We test the sensors first and tell you whether a repair is possible before you pay for anything beyond the diagnosis. If it is not repairable, we explain why and suggest the best option.</p>'),
                    Blocks::callout('info', 'Screen replacement does not break Face ID', 'When we replace a screen, the Face ID sensors stay in your phone. If Face ID stopped working after a repair elsewhere, we can check what went wrong.'),
                    Blocks::cta('Book a Face ID diagnosis'),
                ],
                'faqs' => ['face-id-after-screen', 'price-change', 'not-repairable'],
                'own_faqs' => [],
            ],
            [
                'name' => 'Data Recovery',
                'slug' => 'data-recovery',
                'summary' => 'iPhone will not turn on and your photos are not backed up? We assess the phone and explain the options and cost before any work starts.',
                'intro' => 'iPhone will not turn on and your photos are not backed up? We assess the phone carefully, explain what is possible and only go ahead with your agreement.',
                'icon' => 'data',
                'og_image' => 'images/og/og-iphone-repair.jpg',
                'repairs' => ['data-recovery'],
                'blocks' => [
                    Blocks::h2('How iPhone data recovery works'),
                    Blocks::text('<p>Your iPhone’s data is encrypted and tied to the phone itself, so it cannot simply be copied off the memory chip. Recovery means getting the phone working again for long enough to back it up, by repairing the fault that stops it starting.</p><p>We assess the phone first and explain the chance of success, the work involved and the cost. You decide whether to go ahead.</p>'),
                    Blocks::h2('Give your data the best chance'),
                    Blocks::text('<ul><li>stop trying to charge or restart a phone that has been in water</li><li>do not let anyone "reset" or restore the phone: that erases the data for good</li><li>tell us what matters most, such as photos or messages, so we can prioritise it</li></ul>'),
                    Blocks::callout('warning', 'We never ask for your passcode in advance', 'You will need your passcode to back up the phone once it is working. We will arrange for you to enter it yourself.'),
                    Blocks::cta('Book a data recovery assessment'),
                ],
                'faqs' => ['passcode', 'water-damage-what-to-do', 'not-repairable'],
                'own_faqs' => [],
            ],
            [
                'name' => 'Diagnostics',
                'slug' => 'diagnostics',
                'summary' => 'Not sure what is wrong? We test every function and give you a clear, fixed quote before any repair.',
                'intro' => 'Something wrong but not sure what? We collect your iPhone, test every function and give you a clear, fixed price for the repair. You decide whether to go ahead.',
                'icon' => 'diagnostics',
                'og_image' => 'images/og/og-diagnostics.jpg',
                'repairs' => ['diagnostics'],
                'blocks' => [
                    Blocks::h2('What we test'),
                    Blocks::table('Our diagnostic checks', 'Area | What we check', [
                        'Display | Touch across the whole screen, brightness, True Tone, dead pixels',
                        'Power | Battery health, charging by cable and wirelessly, unexpected shutdowns',
                        'Cameras | Focus, stabilisation, flash and every lens',
                        'Sound | Earpiece, loudspeaker and all microphones',
                        'Security | Face ID or Touch ID',
                        'Connections | Wi-Fi, Bluetooth, mobile signal, buttons and sensors',
                    ]),
                    Blocks::h2('After the diagnosis'),
                    Blocks::text('<p>We tell you exactly what we found, what we recommend and the fixed price. If you go ahead, the repair is carried out straight away; if not, we return your iPhone and you only pay the diagnostic fee shown when you booked.</p>'),
                    Blocks::cta('Book a diagnosis'),
                ],
                'faqs' => ['not-sure-fault', 'price-change', 'when-pay'],
                'own_faqs' => [],
            ],
        ];
    }

    /**
     * Service-specific FAQs get their own topic.
     */
    public static function faqTopic(): FaqTopic
    {
        return FaqTopic::Repairs;
    }
}
