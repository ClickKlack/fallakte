<?php

use App\Import\TeamResolver;
use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;
use Database\Seeders\SeriesSeeder;

beforeEach(function () {
    $this->seed(SeriesSeeder::class);
    $this->series = Series::where('slug', Series::TATORT)->first();
});

test('aliases differing only in accents or case are distinct', function () {
    $a = Team::factory()->for($this->series)->create();
    $b = Team::factory()->for($this->series)->create();

    TeamAlias::create(['team_id' => $a->id, 'raw_value' => 'Borowski und Şahin']);
    TeamAlias::create(['team_id' => $b->id, 'raw_value' => 'Borowski und Sahin']);
    TeamAlias::create(['team_id' => $b->id, 'raw_value' => 'borowski und sahin']);

    expect(TeamAlias::count())->toBe(3);
});

test('team resolver matches raw values exactly', function () {
    $team = Team::factory()->for($this->series)->create();
    TeamAlias::create(['team_id' => $team->id, 'raw_value' => 'Borowski und Şahin']);

    $resolver = new TeamResolver;

    expect($resolver->resolve($this->series, 'Borowski und Şahin'))->toBe($team->id)
        ->and($resolver->resolve($this->series, 'Borowski und Sahin'))->not->toBe($team->id);
});
