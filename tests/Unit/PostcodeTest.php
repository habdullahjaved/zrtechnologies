<?php

use App\Support\Postcode;

it('normalises full UK postcodes', function (string $input, string $expected) {
    expect(Postcode::normalise($input))->toBe($expected);
})->with([
    ['bb24ny', 'BB2 4NY'],
    [' BB2  4NY ', 'BB2 4NY'],
    ['bb10 1aa', 'BB10 1AA'],
    ['M1 1AE', 'M1 1AE'],
    ['EC1A1BB', 'EC1A 1BB'],
]);

it('rejects values that are not full postcodes', function (?string $input) {
    expect(Postcode::normalise($input))->toBeNull();
})->with(['BB2', 'hello', '12345', '', null, 'BB2 4NYY']);

it('extracts the district from a full postcode or a district on its own', function () {
    expect(Postcode::outcode('bb2 4ny'))->toBe('BB2')
        ->and(Postcode::outcode('bb10'))->toBe('BB10')
        ->and(Postcode::outcode('EC1A 1BB'))->toBe('EC1A')
        ->and(Postcode::outcode('not a postcode'))->toBeNull()
        ->and(Postcode::isDistrictOnly('BB2'))->toBeTrue()
        ->and(Postcode::isDistrictOnly('BB2 4NY'))->toBeFalse();
});
