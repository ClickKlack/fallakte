<?php

use App\Enums\EpisodeStatus;
use App\Models\Episode;
use App\Models\Series;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SeriesSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow('2026-11-06 12:00');
    $this->seed(SeriesSeeder::class);
    $this->tatort = Series::where('slug', Series::TATORT)->first();
    $this->polizeiruf = Series::where('slug', Series::POLIZEIRUF)->first();
    $this->actingAs(User::factory()->create());
});

afterEach(fn () => Carbon::setTestNow());

/**
 * Ruft die Liste auf und liefert die Titel der Folgen in Anzeigereihenfolge.
 *
 * @return list<string>
 */
function listTitles(array $query = []): array
{
    $titles = [];
    test()->get('/?'.http_build_query($query))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$titles) {
            $titles = array_column($page->toArray()['props']['episodes']['data'], 'title');
        });

    return $titles;
}

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get('/')->assertRedirect(route('login'));
});

test('page renders with episodes, filters and options', function () {
    $team = Team::factory()->for($this->tatort)->create(['name' => 'Münster']);
    Episode::factory()->for($this->tatort)->for($team)->seen('2025-01-02')->create([
        'number' => 1, 'title' => 'Taxi nach Leipzig', 'first_aired' => '1970-11-29', 'broadcaster' => 'NDR',
    ]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Episodes/Index')
            ->has('episodes.data', 1)
            ->where('episodes.data.0', [
                'id' => Episode::first()->id,
                'series' => ['slug' => 'tatort', 'name' => 'Tatort'],
                'number' => '1',
                'title' => 'Taxi nach Leipzig',
                'first_aired' => '1970-11-29',
                'broadcaster' => 'NDR',
                'team' => ['id' => $team->id, 'name' => 'Münster'],
                'status' => 'aired',
                'seen' => true,
                'seen_at' => '2025-01-02',
                'can_mark_seen' => true,
            ])
            ->where('episodes.meta.total', 1)
            ->where('filters.status', 'all')
            ->has('options.series', 2)
            ->where('options.teams.0.name', 'Münster')
            ->where('options.teams.0.series_slug', 'tatort')
            ->where('options.statuses', ['all', 'seen', 'unseen', 'announced'])
            ->where('options.years', ['min' => 1970, 'max' => 1970]));
});

test('announced episodes cannot be marked as seen', function () {
    Episode::factory()->for($this->tatort)->announced()->create();

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('episodes.data.0.status', 'announced')
        ->where('episodes.data.0.can_mark_seen', false));
});

test('announced episodes whose date has passed are promoted on display', function () {
    $episode = Episode::factory()->for($this->tatort)->create(['first_aired' => '2026-11-08']);
    Carbon::setTestNow('2026-11-08 20:15');

    $this->get('/');

    expect($episode->fresh()->status)->toBe(EpisodeStatus::Aired);
});

describe('filters', function () {
    beforeEach(function () {
        $this->münster = Team::factory()->for($this->tatort)->create(['name' => 'Münster']);
        $this->kiel = Team::factory()->for($this->tatort)->create(['name' => 'Kiel']);
        $this->rostock = Team::factory()->for($this->polizeiruf)->create(['name' => 'Rostock']);

        $make = fn (Series $series, Team $team, int $number, string $title, ?string $date, bool $seen = false) => Episode::factory()
            ->for($series)->for($team)->create(['number' => $number, 'title' => $title, 'first_aired' => $date, 'seen' => $seen]);

        $make($this->tatort, $this->münster, 1, 'Der dunkle Fleck', '2002-10-20', true);
        $make($this->tatort, $this->münster, 2, 'Fakten, Fakten', '2004-03-14');
        $make($this->tatort, $this->kiel, 3, 'Borowski und die Frau', '2010-05-02', true);
        $make($this->tatort, $this->kiel, 4, 'Kiel 100%', '2026-12-20'); // angekündigt
        $make($this->polizeiruf, $this->rostock, 1, 'Einer von uns', '2010-01-01');
        $make($this->polizeiruf, $this->rostock, 2, 'Dunkle Wolken', null); // angekündigt ohne Datum
    });

    test('without filters all episodes are listed', function () {
        expect(listTitles())->toHaveCount(6);
    });

    test('by series', function () {
        expect(listTitles(['series' => 'polizeiruf-110']))->toBe(['Einer von uns', 'Dunkle Wolken']);
    });

    test('by team', function () {
        expect(listTitles(['team' => $this->kiel->id]))->toBe(['Borowski und die Frau', 'Kiel 100%']);
    });

    test('by status', function (string $status, array $expected) {
        expect(listTitles(['status' => $status, 'sort' => 'title']))->toBe($expected);
    })->with([
        'seen' => ['seen', ['Borowski und die Frau', 'Der dunkle Fleck']],
        'unseen (aired only)' => ['unseen', ['Einer von uns', 'Fakten, Fakten']],
        'announced' => ['announced', ['Dunkle Wolken', 'Kiel 100%']],
        'all' => ['all', ['Borowski und die Frau', 'Der dunkle Fleck', 'Dunkle Wolken', 'Einer von uns', 'Fakten, Fakten', 'Kiel 100%']],
    ]);

    test('by year range', function () {
        expect(listTitles(['year_from' => 2004, 'year_to' => 2010, 'sort' => 'date']))
            ->toBe(['Fakten, Fakten', 'Einer von uns', 'Borowski und die Frau']);
    });

    test('by year from only and year to only', function () {
        expect(listTitles(['year_from' => 2026]))->toBe(['Kiel 100%'])
            ->and(listTitles(['year_to' => 2002]))->toBe(['Der dunkle Fleck']);
    });

    test('by title substring, case insensitive', function () {
        expect(listTitles(['q' => 'dunkle', 'sort' => 'title']))->toBe(['Der dunkle Fleck', 'Dunkle Wolken']);
    });

    test('search ignores case of umlauts', function () {
        Episode::factory()->for($this->tatort)->create(['number' => 10, 'title' => 'Über den Dächern']);
        Episode::factory()->for($this->tatort)->create(['number' => 11, 'title' => 'Fahrt über Land']);

        expect(listTitles(['q' => 'über']))->toBe(['Über den Dächern', 'Fahrt über Land'])
            ->and(listTitles(['q' => 'ÜBER']))->toBe(['Über den Dächern', 'Fahrt über Land'])
            ->and(listTitles(['q' => 'dächern']))->toBe(['Über den Dächern']);
    });

    test('search treats wildcard characters literally', function () {
        expect(listTitles(['q' => '100%']))->toBe(['Kiel 100%'])
            ->and(listTitles(['q' => '%']))->toBe(['Kiel 100%'])
            ->and(listTitles(['q' => '_']))->toBe([])
            ->and(listTitles(['q' => '!']))->toBe([]);
    });

    test('combined filters', function () {
        expect(listTitles(['series' => 'tatort', 'team' => $this->münster->id, 'status' => 'unseen', 'year_from' => 2003, 'q' => 'fakt']))
            ->toBe(['Fakten, Fakten'])
            ->and(listTitles(['series' => 'polizeiruf-110', 'team' => $this->münster->id]))->toBe([]);
    });

    test('invalid filter values are ignored', function () {
        expect(listTitles(['status' => 'quatsch', 'series' => 'derrick', 'year_from' => 'abc', 'sort' => 'random']))->toHaveCount(6);
    });

    test('filters are returned for the filter bar', function () {
        $this->get('/?series=tatort&status=seen&q=Fleck')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.series', 'tatort')
                ->where('filters.status', 'seen')
                ->where('filters.q', 'Fleck'));
    });
});

describe('sorting', function () {
    beforeEach(function () {
        $zebra = Team::factory()->for($this->tatort)->create(['name' => 'Zebra']);
        $alpha = Team::factory()->for($this->tatort)->create(['name' => 'Alpha']);

        Episode::factory()->for($this->tatort)->for($zebra)->create(['number' => 2, 'title' => 'Bravo', 'first_aired' => '1990-01-01']);
        Episode::factory()->for($this->tatort)->for($alpha)->create(['number' => 1, 'title' => 'Charlie', 'first_aired' => '2000-01-01']);
        Episode::factory()->for($this->tatort)->create(['number' => 3, 'title' => 'Alpha', 'first_aired' => null, 'team_id' => null]);
        Episode::factory()->for($this->tatort)->unnumbered(after: 1)->create(['title' => 'Einzelfilm', 'first_aired' => '1995-01-01']);
    });

    test('by number including substitute numbers (default)', function () {
        expect(listTitles())->toBe(['Charlie', 'Einzelfilm', 'Bravo', 'Alpha'])
            ->and(listTitles(['direction' => 'desc']))->toBe(['Alpha', 'Bravo', 'Einzelfilm', 'Charlie']);
    });

    test('by date with missing dates last', function () {
        expect(listTitles(['sort' => 'date']))->toBe(['Bravo', 'Einzelfilm', 'Charlie', 'Alpha'])
            ->and(listTitles(['sort' => 'date', 'direction' => 'desc']))->toBe(['Charlie', 'Einzelfilm', 'Bravo', 'Alpha']);
    });

    test('by title', function () {
        expect(listTitles(['sort' => 'title']))->toBe(['Alpha', 'Bravo', 'Charlie', 'Einzelfilm'])
            ->and(listTitles(['sort' => 'title', 'direction' => 'desc']))->toBe(['Einzelfilm', 'Charlie', 'Bravo', 'Alpha']);
    });

    test('by team with episodes without team last', function () {
        expect(listTitles(['sort' => 'team']))->toBe(['Charlie', 'Bravo', 'Einzelfilm', 'Alpha'])
            ->and(listTitles(['sort' => 'team', 'direction' => 'desc']))->toBe(['Bravo', 'Charlie', 'Alpha', 'Einzelfilm']);
    });

    test('substitute number is shown', function () {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('episodes.data.1.number', '1a'));
    });
});

test('episodes of both series with the same number are ordered stably', function () {
    Episode::factory()->for($this->polizeiruf)->create(['number' => 1, 'title' => 'P1']);
    Episode::factory()->for($this->tatort)->create(['number' => 1, 'title' => 'T1']);

    expect(listTitles())->toBe(['T1', 'P1']); // Tatort hat die kleinere Serien-ID
});

describe('pagination', function () {
    beforeEach(fn () => Episode::factory()->for($this->tatort)->count(60)->sequence(fn ($s) => ['number' => $s->index + 1])->create());

    test('first page has 50 episodes by default', function () {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('episodes.data', 50)
            ->where('episodes.meta.total', 60)
            ->where('episodes.meta.last_page', 2));
    });

    test('second page has the rest', function () {
        $this->get('/?page=2')->assertInertia(fn (Assert $page) => $page
            ->has('episodes.data', 10)
            ->where('episodes.data.0.number', '51'));
    });

    test('page size can be chosen', function () {
        $this->get('/?per_page=25')->assertInertia(fn (Assert $page) => $page->has('episodes.data', 25));
    });

    test('pagination links keep the filters', function () {
        $this->get('/?series=tatort&sort=title')->assertInertia(fn (Assert $page) => $page
            ->where('episodes.links.next', fn ($url) => str_contains($url, 'series=tatort') && str_contains($url, 'sort=title') && str_contains($url, 'page=2')));
    });
});
