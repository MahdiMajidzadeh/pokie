<?php

declare(strict_types=1);

use App\Support\Hash;

it('generates hashes of the correct length from the correct alphabet', function () {
    for ($i = 0; $i < 200; $i++) {
        $hash = Hash::generate();

        expect($hash)->toHaveLength(6);

        foreach (str_split($hash) as $char) {
            expect(str_contains(Hash::ALPHABET, $char))->toBeTrue();
        }
    }
});

it('excludes ambiguous characters from the alphabet', function () {
    foreach (['0', '1', 'O', 'I', 'L'] as $char) {
        expect(str_contains(Hash::ALPHABET, $char))->toBeFalse();
    }
});

it('normalizes a valid hash to uppercase', function () {
    expect(Hash::normalize('b26p3p'))->toBe('B26P3P');
    expect(Hash::normalize('B26P3P'))->toBe('B26P3P');
});

it('rejects anything that is not a valid hash', function (?string $input) {
    expect(Hash::normalize($input))->toBeNull();
})->with([
    null,
    '',
    'ABCDE',      // too short
    'ABCDEFG',    // too long
    'ABCD0F',     // contains excluded digit 0
    'ABCDOF',     // contains excluded letter O
    'ABCDIF',     // contains excluded letter I
    'ABCDLF',     // contains excluded letter L
    '!@#$%^',
]);

it('generates effectively unique hashes', function () {
    $hashes = [];

    for ($i = 0; $i < 500; $i++) {
        $hashes[Hash::generate()] = true;
    }

    // Collisions are possible but astronomically unlikely at this sample size.
    expect(count($hashes))->toBe(500);
});
