<?php

use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SeriesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(SeriesSeeder::class));

test('about page shows version, data status and sources', function () {
    config(['app.version' => '1.4.2']);
    $tatort = Series::where('slug', Series::TATORT)->first();
    Team::factory()->for($tatort)->count(2)->create();
    Episode::factory()->for($tatort)->count(3)->create();
    ImportRun::factory()->for($tatort)->create(['finished_at' => '2026-11-05 10:00']);

    $this->actingAs(User::factory()->create())
        ->get(route('about'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('About')
            ->where('version', '1.4.2')
            ->where('repository', 'https://github.com/ClickKlack/fallakte')
            ->where('series.0.slug', 'tatort')
            ->where('series.0.episodes', 3)
            ->where('series.0.teams', 2)
            ->where('series.0.last_import', fn ($value) => str_starts_with($value, '2026-11-05'))
            ->where('series.0.source_title', 'Liste der Tatort-Folgen')
            ->where('series.0.source_url', 'https://de.wikipedia.org/wiki/Liste_der_Tatort-Folgen')
            ->where('series.1.source_url', 'https://de.wikipedia.org/wiki/Liste_der_Polizeiruf-110-Folgen')
            ->where('series.1.last_import', null));
});

test('unfinished import runs are not shown as data status', function () {
    ImportRun::factory()->for(Series::where('slug', Series::TATORT)->first())->create(['finished_at' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('about'))
        ->assertInertia(fn (Assert $page) => $page->where('series.0.last_import', null));
});

test('about page requires login', function () {
    $this->get(route('about'))->assertRedirect(route('login'));
});
