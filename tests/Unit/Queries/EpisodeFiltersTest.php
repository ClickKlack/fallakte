<?php

use App\Queries\EpisodeFilters;

$series = ['tatort', 'polizeiruf-110'];

test('defaults are used without query', function () use ($series) {
    expect(EpisodeFilters::fromArray([], $series)->toArray())->toBe([
        'series' => null, 'team' => null, 'status' => 'all', 'year_from' => null, 'year_to' => null,
        'q' => null, 'sort' => 'number', 'direction' => 'asc', 'per_page' => 50,
    ]);
});

test('valid values are taken over', function () use ($series) {
    $filters = EpisodeFilters::fromArray([
        'series' => 'tatort', 'team' => '12', 'status' => 'unseen', 'year_from' => '1990', 'year_to' => '1999',
        'q' => '  Münster ', 'sort' => 'date', 'direction' => 'desc', 'per_page' => '100',
    ], $series);

    expect($filters->toArray())->toBe([
        'series' => 'tatort', 'team' => 12, 'status' => 'unseen', 'year_from' => 1990, 'year_to' => 1999,
        'q' => 'Münster', 'sort' => 'date', 'direction' => 'desc', 'per_page' => 100,
    ]);
});

test('invalid values are ignored', function (array $query, string $key, mixed $expected) use ($series) {
    expect(EpisodeFilters::fromArray($query, $series)->toArray()[$key])->toBe($expected);
})->with([
    'unknown series' => [['series' => 'derrick'], 'series', null],
    'team not numeric' => [['team' => 'abc'], 'team', null],
    'team zero' => [['team' => '0'], 'team', null],
    'negative team' => [['team' => '-3'], 'team', null],
    'unknown status' => [['status' => 'quatsch'], 'status', 'all'],
    'year too small' => [['year_from' => '1800'], 'year_from', null],
    'year too large' => [['year_to' => '3000'], 'year_to', null],
    'year not numeric' => [['year_from' => '19x0'], 'year_from', null],
    'unknown sort' => [['sort' => 'random'], 'sort', 'number'],
    'unknown direction' => [['direction' => 'sideways'], 'direction', 'asc'],
    'unsupported page size' => [['per_page' => '1000'], 'per_page', 50],
    'empty search' => [['q' => '   '], 'q', null],
    'array instead of string' => [['q' => ['x']], 'q', null],
]);

test('swapped year range is corrected', function () use ($series) {
    $filters = EpisodeFilters::fromArray(['year_from' => '2010', 'year_to' => '2000'], $series);

    expect($filters->yearFrom)->toBe(2000)
        ->and($filters->yearTo)->toBe(2010);
});

test('search is limited to 100 characters', function () use ($series) {
    expect(mb_strlen(EpisodeFilters::fromArray(['q' => str_repeat('ä', 150)], $series)->search))->toBe(100);
});
