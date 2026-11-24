<?php

use App\Support\Phone;

it('normalises UK numbers to E.164', function (string $input) {
    expect(Phone::toE164($input))->toBe('+447360127538');
})->with([
    'national' => '07360 127538',
    'national without spaces' => '07360127538',
    'international' => '+44 7360 127538',
    'international with brackets' => '+44 (0)7360 127538',
]);

it('formats E.164 numbers for display in UK national format', function () {
    expect(Phone::display('+447360127538'))->toBe('07360 127538');
});

it('builds tel and WhatsApp links', function () {
    expect(Phone::telHref('07360 127538'))->toBe('tel:+447360127538')
        ->and(Phone::whatsAppUrl('+447360127538'))->toBe('https://wa.me/447360127538')
        ->and(Phone::whatsAppUrl('+447360127538', 'Hi there & hello'))->toBe('https://wa.me/447360127538?text=Hi%20there%20%26%20hello');
});

it('returns unparseable input unchanged rather than failing', function () {
    expect(Phone::toE164(' not a number '))->toBe('not a number')
        ->and(Phone::display('abc'))->toBe('abc');
});
