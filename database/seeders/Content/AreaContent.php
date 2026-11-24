<?php

namespace Database\Seeders\Content;

/**
 * Local content for the location pages of the towns we collect from. Written
 * as a starting point for the owner to check and add to (the areas are still
 * drafts). Each page must stay genuinely different: the unique-content check
 * keeps a page noindex until it is long enough and not too similar to another.
 *
 * @phpstan-import-type Block from Blocks
 */
final class AreaContent
{
    /**
     * @return array<string, array{intro: string, landmarks: string, blocks: list<Block>}> keyed by area slug
     */
    public static function all(): array
    {
        return [
            'blackburn' => [
                'intro' => 'iPhone repairs across Blackburn without the trip into town. We collect your phone from your home or workplace anywhere in BB1 and BB2, repair it with quality-tested parts and bring it back to your door.',
                'landmarks' => "We collect all over Blackburn: from Audley, Brookhouse and Little Harwood in the north and east, through Shadsworth, Intack and Mill Hill, to Feniscowles, Pleasington, Livesey and Cherry Tree in the south-west. Wilpshire, Lammack, Beardwood and Roe Lee are covered too.\n\nPlenty of our Blackburn customers book a collection at work, whether that is an office in the town centre near the Cathedral and King George’s Hall, a shift at the Royal Blackburn Teaching Hospital or a day at Blackburn College. Students and staff can arrange a handover near their building, so the phone is back with them before the end of the day.\n\nIf you are out at Witton Country Park, Corporation Park or Queen’s Park, or watching Rovers at Ewood Park, you do not need to plan your weekend around a phone shop: book a Saturday window and we will meet you at home afterwards.",
                'blocks' => [
                    Blocks::h2('Why Blackburn customers book with us'),
                    Blocks::text('<p>Blackburn is our home town, so this is where we collect most often. Instead of driving into the centre, finding parking near The Mall and waiting at a counter, you choose a collection window online and stay where you are. Collection and return within Blackburn is free.</p><p>The most common Blackburn repairs are cracked screens and tired batteries, followed by charging ports clogged with pocket fluff. Most of these are finished the same day we collect, so your iPhone is often back with you by the evening.</p>'),
                    Blocks::h2('Collection times in Blackburn'),
                    Blocks::text('<p>We collect in morning, afternoon and late-afternoon windows on weekdays, and in morning and afternoon windows on Saturdays. Choose the window that suits you when you book: earlier windows give us more time to return your phone the same day.</p>'),
                    Blocks::h2('Repairs for Blackburn businesses'),
                    Blocks::text('<p>From shops on Penny Street and King William Street to offices, garages and care homes on the edge of town, plenty of Blackburn businesses rely on a handful of iPhones. When one breaks, the whole day slows down. We can collect several phones in one visit, quote for them together and bring them back with a job reference for each, so your records stay straight. Use our business enquiry form to tell us what you need, or simply book each phone online as usual.</p>'),
                ],
            ],
            'darwen' => [
                'intro' => 'iPhone repair for Darwen and Hoddlesden with free collection and return. We come to you anywhere in BB3, fix your iPhone with quality-tested parts and bring it back, so there is no need to travel into Blackburn.',
                'landmarks' => "Darwen sits just south of Blackburn, and we cover the whole BB3 district: the town centre around the market and the Carnegie library, Whitehall, Sudell, Earcroft and Lower Darwen, up to the edges of the moors and out to Hoddlesden.\n\nYou probably know the town by Darwen Tower, the Jubilee Tower on Darwen Hill, and by the tall India Mill chimney that dominates the skyline. Walkers heading up to the tower or through Sunnyhurst Woods and Bold Venture Park are exactly the people who drop their phones on uneven ground, and cracked screens are the repair we do most in Darwen.\n\nFor homes on the hillside streets we can meet you at the door at a time that suits you, so you do not have to carry a broken phone down into town and back.",
                'blocks' => [
                    Blocks::h2('A local service for Darwen'),
                    Blocks::text('<p>Before collection and delivery, getting a phone repaired in Darwen usually meant a bus or a drive to Blackburn. Now you book online, we collect from your home or workplace in BB3 and we return it once it is fixed. Collection and return in Darwen is free, and you only pay once your phone is back.</p><p>Batteries are the second most common Darwen repair. Cold weather on the moors makes a worn battery switch off early, and a new battery usually solves it within a couple of hours of collection.</p>'),
                    Blocks::h2('Booking a Darwen collection'),
                    Blocks::text('<p>Enter your Darwen postcode when you book and you will see the collection windows available, Monday to Saturday. If you will be at work in Blackburn rather than at home, enter that address instead; we cover both.</p>'),
                    Blocks::h2('Tips for Darwen hill walkers'),
                    Blocks::text('<p>If you head up to the tower with your phone for photos, a case with a raised lip and a glass screen protector will save most screens from a fall on the rocky path. Keep the phone in a zipped pocket rather than a back pocket when you climb stiles, and in winter keep it close to your body: a cold battery drains far faster and can switch the phone off just when you want a picture of the view across to Winter Hill. If it does get cracked or soaked on the moor, book a collection when you get home.</p>'),
                ],
            ],
            'accrington' => [
                'intro' => 'iPhone repair in Accrington, Oswaldtwistle and Church, collected from your door. We cover the whole BB5 district: we pick up your iPhone, repair it and bring it straight back.',
                'landmarks' => "Our Accrington collections cover the town centre around the Market Hall and the Arndale, plus Church, Clayton-le-Moors, Huncoat, Baxenden and Oswaldtwistle, all in the BB5 postcode district.\n\nAccrington is famous for its hard red “Nori” bricks, for Accrington Stanley at the Wham Stadium and for the Tiffany glass collection at the Haworth Art Gallery. Over in Oswaldtwistle, Oswaldtwistle Mills brings in shoppers from across Lancashire. Wherever you are in the area, we can collect from your home, or from your workplace if you would rather hand the phone over during the day.\n\nOak Hill Park and the walks up towards the moors are lovely, and they are also where plenty of iPhones meet the ground. If yours has a cracked screen or a damaged back, book a collection and we will take it from there.\n\nWe also cover the roads out towards Rishton and the Hyndburn villages. If you are not sure whether your street is in BB5, enter your postcode above for an instant answer before you book.",
                'blocks' => [
                    Blocks::h2('Collection and return across BB5'),
                    Blocks::text('<p>Accrington and Oswaldtwistle are a short drive from our Blackburn base, so we collect here every weekday and on Saturdays. A small collection fee applies in BB5, and it is shown clearly before you confirm your booking, alongside the repair price.</p><p>Screens, batteries and charging ports are the repairs Accrington customers book most. Most are completed on the day we collect, and we test every function before your iPhone comes back to you.</p>'),
                    Blocks::h2('Not sure what is wrong?'),
                    Blocks::text('<p>If your iPhone is acting up and you are not sure why, choose "Not sure what’s wrong?" when you book. We will test it fully and give you a fixed price before any repair starts.</p>'),
                    Blocks::h2('Students, shift workers and match days'),
                    Blocks::text('<p>Many Accrington customers book around work patterns that do not suit shop opening hours: early shifts, late finishes or a full day at college. Collection windows run through the afternoon into the early evening on weekdays, and on Saturdays too, so you can hand your phone over before a home game at Stanley and have it back afterwards. If you live in Oswaldtwistle but work in Accrington town centre, book the collection at work and the return at home: we plan both journeys for you.</p>'),
                ],
            ],
            'great-harwood' => [
                'intro' => 'iPhone repair in Great Harwood and Langho with collection from your door. We pick up your iPhone anywhere in BB6, repair it with quality-tested parts and return it, usually the same day.',
                'landmarks' => "Great Harwood is a close-knit town, and the clock tower in the town square, built in memory of the chemist John Mercer, is the place everyone gives directions from. We collect from homes and workplaces all around it, and out to Langho and the surrounding villages in the BB6 district.\n\nBecause Great Harwood sits between Blackburn, Accrington and the Ribble Valley, a phone repair used to mean a trip in one direction or another. With collection and return you can stay put: book online, choose a window and we come to you.\n\nMany of our BB6 customers commute to Blackburn or Clitheroe. If it is easier, book the collection for your workplace and the return for home, and we will plan the route around you.\n\nOut towards Martholme and the lanes beyond Langho, houses can be tricky to find. Call or message us after booking with any directions, such as a farm name or a gate code, and we will arrive at the right door first time.",
                'blocks' => [
                    Blocks::h2('Popular repairs in Great Harwood'),
                    Blocks::text('<p>The repairs we see most in BB6 are cracked screens, batteries that no longer last the day and charging ports that have stopped holding the cable. Each has a fixed price for your model, shown before you book, and you pay nothing until your iPhone is back in your hand.</p><p>A small collection fee applies in Great Harwood and Langho. It is shown before you confirm, and it covers both the collection and the return.</p>'),
                    Blocks::h2('Look after your data'),
                    Blocks::text('<p>Most repairs do not touch your photos or messages, but we always recommend a quick iCloud backup before we collect. We never ask for your passcode in our booking form.</p>'),
                    Blocks::h2('Helping older relatives in Great Harwood and Langho'),
                    Blocks::text('<p>A lot of our bookings in BB6 are made by sons, daughters and grandchildren for a parent or grandparent whose iPhone has stopped working. You can book online on their behalf, enter their address for the collection and add your own mobile number, so we keep you updated while we repair it. We hand the phone back in person, check it works as it should and show them anything that has changed, such as a fresh screen protector. Nobody has to make a trip into town or wait around in a shop.</p>'),
                ],
            ],
            'clitheroe' => [
                'intro' => 'iPhone repair for Clitheroe and the Ribble Valley villages, collected and returned to your door. We come to you in BB7, repair your iPhone and bring it back, so there is no need to drive into Blackburn.',
                'landmarks' => "Clitheroe is the heart of the Ribble Valley, with its Norman castle keep looking down over the market town and Pendle Hill on the horizon. We collect from homes and businesses across the town, from the streets around the castle and Castle Street to the newer estates on the edge of town, and from the villages nearby, including Whalley, Barrow, Chatburn, Waddington and Sabden.\n\nRibble Valley life means plenty of time outdoors: walks up Pendle, afternoons by the river at Edisford Bridge and visits to Whalley Abbey. Phones get dropped on stone paths and soaked in sudden showers, and we repair both kinds of damage.\n\nFor farms and homes outside the town, call or message us after booking with any directions and we will find you.\n\nOn market days the town centre gets busy, so if you live near Moor Lane or Lowergate and parking is tight, tell us the best place to meet and we will hand your phone over there.",
                'blocks' => [
                    Blocks::h2('Collection from Clitheroe and the villages'),
                    Blocks::text('<p>Clitheroe is the furthest of our core areas from Blackburn, so a collection fee applies. It is shown clearly before you confirm your booking and covers both journeys. For many customers it is still cheaper and quicker than driving to a shop and back twice.</p><p>Earlier collection windows give us the best chance to return your phone the same day. Water damage and data recovery take longer, and we will always tell you what to expect.</p>'),
                    Blocks::h2('Wet phone after a walk?'),
                    Blocks::text('<p>Switch it off straight away, do not charge it, and skip the rice. Then book a water damage assessment: the sooner we clean the board, the better the chance of saving the phone and your photos.</p>'),
                    Blocks::h2('Ribble Valley businesses and holiday lets'),
                    Blocks::text('<p>Farm shops, pubs, cafés and holiday cottages across the valley often run their card machines, bookings and messages from a single iPhone. When that phone breaks it can cost a day of trade. Tell us it is a work phone when you book and we will do our best to fit the collection around opening hours. If you have several devices, our business enquiry form lets us quote for them together and collect them on one visit, saving everyone a journey along the A59.</p>'),
                ],
            ],
        ];
    }
}
