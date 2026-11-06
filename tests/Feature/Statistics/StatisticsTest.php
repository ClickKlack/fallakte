<?php

use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use App\Models\User;
use App\Services\Statistics;
use Database\Seeders\SeriesSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Bekannter Datenbestand:
 * Tatort, Team Münster (6 ausgestrahlt, 4 gesehen) und Team Kiel (2 ausgestrahlt, 0 gesehen),
 * Polizeiruf, Team Rostock (2 ausgestrahlt, 1 gesehen ohne Datum),
 * dazu angekündigte Folgen (gesehen markiert!) und eine Folge ohne Team.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-11-06 12:00');
    $this->seed(SeriesSeeder::class);
    $tatort = Series::where('slug', Series::TATORT)->first();
    $polizeiruf = Series::where('slug', Series::POLIZEIRUF)->first();

    $this->münster = Team::factory()->for($tatort)->create(['name' => 'Thiel und Boerne']);
    $this->kiel = Team::factory()->for($tatort)->create(['name' => 'Borowski']);
    $this->rostock = Team::factory()->for($polizeiruf)->create(['name' => 'Bukow und König']);

    $make = fn (Series $s, ?Team $t, string $aired, bool $seen = false, ?string $seenAt = null) => Episode::factory()
        ->for($s)->create(['team_id' => $t?->id, 'first_aired' => $aired, 'seen' => $seen, 'seen_at' => $seenAt]);

    $make($tatort, $this->münster, '2002-10-20', true, '2024-01-10');
    $make($tatort, $this->münster, '2002-12-01', true, '2024-05-05');
    $make($tatort, $this->münster, '2004-03-14', true, '2025-02-02');
    $make($tatort, $this->münster, '2004-06-01', true);           // gesehen ohne Datum
    $make($tatort, $this->münster, '2005-01-01');
    $make($tatort, $this->münster, '2005-02-01');
    $make($tatort, $this->kiel, '2003-05-05');
    $make($tatort, $this->kiel, '2005-05-05');
    $make($polizeiruf, $this->rostock, '2010-01-01', true);       // gesehen ohne Datum
    $make($polizeiruf, $this->rostock, '2011-01-01');
    $make($tatort, null, '2000-01-01', true, '2023-07-07');        // ohne Team

    // Angekündigt (Zukunft / ohne Datum) – zählen nirgends, auch wenn (fehlerhaft) gesehen markiert
    Episode::factory()->for($tatort)->for($this->münster)->announced()->create(['seen' => true, 'seen_at' => '2026-01-01']);
    Episode::factory()->for($polizeiruf)->for($this->rostock)->withoutDate()->create();

    $this->stats = app(Statistics::class);
});

afterEach(fn () => Carbon::setTestNow());

test('totals per series and overall without announced episodes', function () {
    expect($this->stats->totals())->toBe([
        'series' => [
            ['slug' => 'tatort', 'name' => 'Tatort', 'aired' => 9, 'seen' => 5],
            ['slug' => 'polizeiruf-110', 'name' => 'Polizeiruf 110', 'aired' => 2, 'seen' => 1],
        ],
        'total' => ['aired' => 11, 'seen' => 6],
    ]);
});

test('progress by air year is continuous and excludes announced episodes', function () {
    $years = collect($this->stats->byAirYear())->keyBy('year');

    expect($years->keys()->all())->toBe(range(2000, 2011))
        ->and($years[2000])->toBe(['year' => 2000, 'aired' => 1, 'seen' => 1])
        ->and($years[2001])->toBe(['year' => 2001, 'aired' => 0, 'seen' => 0])
        ->and($years[2002])->toBe(['year' => 2002, 'aired' => 2, 'seen' => 2])
        ->and($years[2004])->toBe(['year' => 2004, 'aired' => 2, 'seen' => 2])
        ->and($years[2005])->toBe(['year' => 2005, 'aired' => 3, 'seen' => 0])
        ->and($years[2010])->toBe(['year' => 2010, 'aired' => 1, 'seen' => 1])
        ->and($years->sum('aired'))->toBe(11);
});

test('progress by air year can be limited to one series', function () {
    expect(collect($this->stats->byAirYear('polizeiruf-110'))->pluck('year')->all())->toBe([2010, 2011]);
});

test('seen per year counts only episodes with a seen date', function () {
    expect($this->stats->seenPerYear())->toBe([
        ['year' => 2023, 'seen' => 1],
        ['year' => 2024, 'seen' => 2],
        ['year' => 2025, 'seen' => 1],
    ])
        ->and($this->stats->seenWithoutDate())->toBe(2)
        ->and($this->stats->seenPerYear('polizeiruf-110'))->toBe([])
        ->and($this->stats->seenWithoutDate('polizeiruf-110'))->toBe(1);
});

test('top teams by progress need a minimum of aired episodes', function () {
    $teams = $this->stats->teams(limit: 5, minAired: 2);

    expect(array_column($teams['progress'], 'name'))->toBe(['Thiel und Boerne', 'Bukow und König'])
        ->and($teams['progress'][0])->toMatchArray(['aired' => 6, 'seen' => 4, 'unseen' => 2])
        ->and(array_column($this->stats->teams(minAired: 5)['progress'], 'name'))->toBe(['Thiel und Boerne']);
});

test('teams with most unseen episodes', function () {
    $teams = $this->stats->teams();

    expect(array_map(fn ($t) => [$t['name'], $t['unseen']], $teams['unseen']))->toBe([
        ['Borowski', 2],
        ['Thiel und Boerne', 2],
        ['Bukow und König', 1],
    ]);
});

test('team rankings respect the limit and the series', function () {
    expect($this->stats->teams(limit: 1)['unseen'])->toHaveCount(1)
        ->and(array_column($this->stats->teams(seriesSlug: 'polizeiruf-110')['unseen'], 'name'))->toBe(['Bukow und König']);
});

test('last import run per series', function () {
    $tatort = Series::where('slug', Series::TATORT)->first();
    ImportRun::factory()->for($tatort)->create(['started_at' => '2026-11-01 10:00', 'created_count' => 99]);
    ImportRun::factory()->for($tatort)->create([
        'started_at' => '2026-11-05 10:00', 'finished_at' => '2026-11-05 10:01',
        'created_count' => 1, 'updated_count' => 2, 'skipped_count' => 3, 'error_count' => 1, 'errors' => 'Fehler X',
    ]);

    $imports = $this->stats->lastImports();

    expect($imports[0]['series']['slug'])->toBe('tatort')
        ->and($imports[0]['run'])->toMatchArray(['created' => 1, 'updated' => 2, 'skipped' => 3, 'errors' => 1, 'error_text' => 'Fehler X'])
        ->and($imports[1]['run'])->toBeNull();
});

test('dashboard page renders with all statistics', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('series', null)
            ->where('totals.total', ['aired' => 11, 'seen' => 6])
            ->has('byAirYear', 12)
            ->has('seenPerYear', 3)
            ->where('seenWithoutDate', 2)
            ->has('teams.progress')
            ->has('teams.unseen')
            ->has('imports', 2));
});

test('dashboard can be limited to a series and ignores unknown values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard', ['series' => 'polizeiruf-110']))
        ->assertInertia(fn (Assert $page) => $page->where('series', 'polizeiruf-110')->has('byAirYear', 2));

    $this->actingAs($user)->get(route('dashboard', ['series' => 'derrick']))
        ->assertInertia(fn (Assert $page) => $page->where('series', null));
});

test('announced episodes become part of the statistics on their air date', function () {
    Episode::factory()->for(Series::where('slug', Series::TATORT)->first())->create(['first_aired' => '2026-11-08']);
    Carbon::setTestNow('2026-11-08 20:15');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('totals.total.aired', 12));
});
