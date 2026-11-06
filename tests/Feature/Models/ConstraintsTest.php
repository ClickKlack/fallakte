<?php

use App\Models\Episode;
use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;
use Illuminate\Database\UniqueConstraintViolationException;

test('episode number is unique per series', function () {
    $series = Series::factory()->create();
    Episode::factory()->for($series)->create(['number' => 42]);

    Episode::factory()->for($series)->create(['number' => 42]);
})->throws(UniqueConstraintViolationException::class);

test('same episode number is allowed in different series', function () {
    Episode::factory()->for(Series::factory())->create(['number' => 42]);
    Episode::factory()->for(Series::factory())->create(['number' => 42]);

    expect(Episode::where('number', 42)->count())->toBe(2);
});

test('alias raw value is unique per series', function () {
    $series = Series::factory()->create();
    $teamA = Team::factory()->for($series)->create();
    $teamB = Team::factory()->for($series)->create();
    TeamAlias::factory()->for($teamA)->create(['raw_value' => 'Thiel, Boerne']);

    TeamAlias::factory()->for($teamB)->create(['raw_value' => 'Thiel, Boerne']);
})->throws(UniqueConstraintViolationException::class);

test('same alias raw value is allowed in different series', function () {
    TeamAlias::factory()->for(Team::factory())->create(['raw_value' => 'Schmidt']);
    TeamAlias::factory()->for(Team::factory())->create(['raw_value' => 'Schmidt']);

    expect(TeamAlias::where('raw_value', 'Schmidt')->count())->toBe(2);
});

test('series slug is unique', function () {
    Series::factory()->tatort()->create();
    Series::factory()->tatort()->create();
})->throws(UniqueConstraintViolationException::class);
