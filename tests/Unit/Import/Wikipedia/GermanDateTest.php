<?php

use App\Import\Wikipedia\GermanDate;

test('dates are read from supported formats', function (string $cell, string $expected) {
    expect(GermanDate::parse($cell)?->toDateString())->toBe($expected);
})->with([
    'DatumZelle' => ['{{DatumZelle|1970-11-29}}', '1970-11-29'],
    'DatumZelle with anchor and ref' => ['{{DatumZelle|1971-01-10}}{{Anker|A1971}}<ref>x</ref>', '1971-01-10'],
    'abbreviated month' => ['13. Sep. 2026', '2026-09-13'],
    'padding template' => ['{{0}}8. Sep. 1985', '1985-09-08'],
    'Mär' => ['{{0}}2. Mär. 1986', '1986-03-02'],
    'full month' => ['19. Mai 1985', '1985-05-19'],
    'full month long' => ['24. Dezember 2026', '2026-12-24'],
    'Austrian month' => ['3. Jänner 1988', '1988-01-03'],
    'numeric' => ['24.06.1979', '1979-06-24'],
]);

test('empty or invalid dates yield null', function (string $cell) {
    expect(GermanDate::parse($cell))->toBeNull();
})->with([
    'empty' => [''],
    'placeholder' => ['{{DatumZelle|2026-MM-TT}}'],
    'impossible date' => ['{{DatumZelle|2026-02-30}}'],
    'unknown month' => ['3. Foo 2026'],
    'text only' => ['noch offen'],
]);

test('unreadable dates are detected', function () {
    expect(GermanDate::isUnreadable('irgendwann 2027'))->toBeTrue()
        ->and(GermanDate::isUnreadable(''))->toBeFalse()
        ->and(GermanDate::isUnreadable('{{DatumZelle|2026-01-01}}'))->toBeFalse();
});
