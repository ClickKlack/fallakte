<?php

use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;

test('series has teams, episodes and import runs', function () {
    $series = Series::factory()->create();
    $team = Team::factory()->for($series)->create();
    Episode::factory()->count(2)->for($series)->for($team)->create();
    ImportRun::factory()->for($series)->create();

    expect($series->teams)->toHaveCount(1)
        ->and($series->episodes)->toHaveCount(2)
        ->and($series->importRuns)->toHaveCount(1);
});

test('team belongs to series and has episodes and aliases', function () {
    $team = Team::factory()->create();
    Episode::factory()->for($team->series)->for($team)->create();
    TeamAlias::factory()->for($team)->count(2)->create();

    expect($team->series)->toBeInstanceOf(Series::class)
        ->and($team->episodes)->toHaveCount(1)
        ->and($team->aliases)->toHaveCount(2);
});

test('episode belongs to series and optionally to a team', function () {
    $team = Team::factory()->create();
    $withTeam = Episode::factory()->for($team->series)->for($team)->create();
    $withoutTeam = Episode::factory()->for($team->series)->create();

    expect($withTeam->series->is($team->series))->toBeTrue()
        ->and($withTeam->team->is($team))->toBeTrue()
        ->and($withoutTeam->team)->toBeNull();
});

test('alias takes over the series of its team', function () {
    $team = Team::factory()->create();
    $alias = TeamAlias::factory()->for($team)->create();

    expect($alias->series_id)->toBe($team->series_id)
        ->and($alias->series->is($team->series))->toBeTrue();
});

test('alias follows its team when moved to another team', function () {
    $alias = TeamAlias::factory()->create();
    $otherTeam = Team::factory()->create();

    $alias->update(['team_id' => $otherTeam->id]);

    expect($alias->fresh()->series_id)->toBe($otherTeam->series_id);
});

test('import run belongs to series', function () {
    $run = ImportRun::factory()->create();

    expect($run->series)->toBeInstanceOf(Series::class)
        ->and($run->started_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('deleting a team keeps its episodes without team and removes its aliases', function () {
    $team = Team::factory()->create();
    $episode = Episode::factory()->for($team->series)->for($team)->create();
    TeamAlias::factory()->for($team)->create();

    $team->delete();

    expect($episode->fresh()->team_id)->toBeNull()
        ->and(TeamAlias::count())->toBe(0);
});
