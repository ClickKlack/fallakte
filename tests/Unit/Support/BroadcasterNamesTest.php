<?php

use App\Support\BroadcasterNames;

test('broadcaster values are split into single broadcasters', function (?string $value, array $expected) {
    expect(BroadcasterNames::split($value))->toBe($expected);
})->with([
    'single' => ['NDR', ['NDR']],
    'slash' => ['SWR/SRF', ['SWR', 'SRF']],
    'name with space' => ['Radio Bremen/WDR', ['Radio Bremen', 'WDR']],
    'und' => ['NDR und MDR', ['NDR', 'MDR']],
    'years in parentheses' => ['Fernsehen der DDR (1974); MDR (2011)', ['Fernsehen der DDR', 'MDR']],
    'duplicates removed' => ['WDR/WDR', ['WDR']],
    'empty' => ['', []],
    'null' => [null, []],
]);

test('distinct broadcasters are sorted alphabetically', function () {
    expect(BroadcasterNames::distinct(['SWR/SRF', 'NDR', null, 'NDR und MDR', 'BR']))
        ->toBe(['BR', 'MDR', 'NDR', 'SRF', 'SWR']);
});

test('like patterns escape wildcards', function () {
    expect(BroadcasterNames::likePatterns('A_B%'))->toContain('A!_B!%/%', '%/A!_B!%');
});
