<?php

use App\Support\Money;

it('formats pence as pounds, dropping pence on whole amounts', function (int $pence, string $expected) {
    expect(Money::format($pence))->toBe($expected);
})->with([
    'whole pounds' => [5900, '£59'],
    'pence' => [5999, '£59.99'],
    'thousands' => [105900, '£1,059'],
    'zero' => [0, '£0'],
]);

it('parses pounds typed by people into pence', function (mixed $input, ?int $expected) {
    expect(Money::parsePounds($input))->toBe($expected);
})->with([
    ['59', 5900],
    ['59.9', 5990],
    ['£1,059.99', 105999],
    [' 12.50 ', 1250],
    [12.5, 1250],
    ['-5', null],
    ['12.345', null],
    ['abc', null],
    [null, null],
]);

it('writes pence as a two-decimal pounds string', function () {
    expect(Money::toPounds(5900))->toBe('59.00')
        ->and(Money::toPounds(105999))->toBe('1059.99');
});
