<?php

use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use App\Settings\SiteSettings;
use App\Settings\SocialSettings;

it('seeds the business facts from the brief', function () {
    $business = app(BusinessSettings::class);

    expect($business->legal_name)->toBe('ZR TECHNOLOGIES LTD')
        ->and($business->trading_name)->toBe('ZR Technologies')
        ->and($business->company_number)->toBe('17589309')
        ->and($business->phone)->toBe('+447360127538')
        ->and($business->whatsapp)->toBe('+447360127538')
        ->and($business->email)->toBe('mrumar@zrtechnologiesltd.com')
        ->and($business->website_url)->toBe('https://zrtechnologiesltd.com')
        ->and($business->registeredOfficeOneLine())->toBe('98 Moorgate Street, Blackburn, Lancashire, BB2 4NY')
        ->and($business->show_street_address)->toBeFalse();
});

it('starts with indexing switched off and only the Google Business Profile linked', function () {
    expect(app(SiteSettings::class)->allow_indexing)->toBeFalse()
        ->and(app(SocialSettings::class)->sameAs())->toBe(['https://share.google/rC3I3iXS9seMC8iDk'])
        ->and(app(SocialSettings::class)->filledNetworks())->toBe([]);
});

it('shows round-the-clock opening as Open 24 hours, every day', function () {
    expect(app(OpeningHoursSettings::class)->summary())->toBe(['Every day' => 'Open 24 hours'])
        ->and(app(OpeningHoursSettings::class)->isOpenAllWeek())->toBeTrue();

    $this->get(route('home'))->assertSee('Open 24 hours')->assertSee('Call or WhatsApp 24/7');
});

/**
 * Weekday hours for the grouping tests.
 */
function useWeekdayHours(): void
{
    $weekday = ['is_open' => true, 'opens' => '09:00', 'closes' => '19:00'];
    $hours = app(OpeningHoursSettings::class);
    $hours->days = [
        'monday' => $weekday, 'tuesday' => $weekday, 'wednesday' => $weekday, 'thursday' => $weekday, 'friday' => $weekday,
        'saturday' => ['is_open' => true, 'opens' => '10:00', 'closes' => '17:00'],
        'sunday' => ['is_open' => false, 'opens' => null, 'closes' => null],
    ];
    $hours->save();
}

it('groups consecutive days with the same opening hours', function () {
    useWeekdayHours();

    expect(app(OpeningHoursSettings::class)->summary())->toBe([
        'Monday – Friday' => '9:00am – 7:00pm',
        'Saturday' => '10:00am – 5:00pm',
        'Sunday' => 'Closed',
    ]);
});

it('does not group days that are not consecutive', function () {
    useWeekdayHours();
    $hours = freshSettings(OpeningHoursSettings::class);
    $days = $hours->days;
    $days['wednesday'] = ['is_open' => false, 'opens' => null, 'closes' => null];
    $hours->days = $days;
    $hours->save();

    expect(app(OpeningHoursSettings::class)->summary())->toBe([
        'Monday – Tuesday' => '9:00am – 7:00pm',
        'Wednesday' => 'Closed',
        'Thursday – Friday' => '9:00am – 7:00pm',
        'Saturday' => '10:00am – 5:00pm',
        'Sunday' => 'Closed',
    ]);
});

it('formats 24-hour times as 12-hour times', function (string $time, string $expected) {
    expect(OpeningHoursSettings::formatTime($time))->toBe($expected);
})->with([
    ['00:00', '12:00am'],
    ['09:30', '9:30am'],
    ['12:00', '12:00pm'],
    ['19:05', '7:05pm'],
]);

it('lists only filled social profiles', function () {
    $social = app(SocialSettings::class);
    $social->facebook = 'https://www.facebook.com/zrtechnologies';
    $social->google_business_profile = 'https://maps.app.goo.gl/example';
    $social->save();

    $social = app(SocialSettings::class);

    expect($social->filledNetworks())->toBe([
        'facebook' => ['label' => 'Facebook', 'url' => 'https://www.facebook.com/zrtechnologies'],
    ])->and($social->sameAs())->toBe([
        'https://maps.app.goo.gl/example',
        'https://www.facebook.com/zrtechnologies',
    ]);
});
