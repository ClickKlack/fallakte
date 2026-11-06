<?php

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
    $this->team = Team::factory()->for($this->tatort)->create(['name' => 'Thiel und Boerne']);
    $this->episode = Episode::factory()->for($this->tatort)->for($this->team)->create([
        'number' => 42, 'title' => 'Wikipedia-Titel', 'first_aired' => '2002-10-20',
        'broadcaster' => 'WDR', 'wikipedia_ref' => 'Tatort: Wikipedia-Titel',
    ]);
    $this->actingAs(User::factory()->create());
});

afterEach(fn () => Carbon::setTestNow());

/** Formulardaten der Testfolge mit Änderungen. */
function formData(array $changes = []): array
{
    $e = test()->episode;

    return array_merge([
        'number' => $e->number, 'title' => $e->title, 'first_aired' => $e->first_aired->toDateString(),
        'broadcaster' => $e->broadcaster, 'team_id' => $e->team_id, 'seen' => false, 'seen_at' => null,
    ], $changes);
}

describe('edit page', function () {
    test('renders with episode data and options', function () {
        $this->episode->update(['manual_fields' => ['title']]);

        $this->get(route('episodes.edit', ['episode' => $this->episode, 'return_to' => '?series=tatort']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Episodes/Edit')
                ->where('episode.id', $this->episode->id)
                ->where('episode.title', 'Wikipedia-Titel')
                ->where('episode.first_aired', '2002-10-20')
                ->where('episode.team_id', $this->team->id)
                ->where('episode.manual_fields', ['title'])
                ->where('episode.from_wikipedia', true)
                ->where('episode.series.slug', 'tatort')
                ->has('options.series', 2)
                ->where('options.teams.0.name', 'Thiel und Boerne')
                ->where('options.broadcasters', ['WDR'])
                ->where('returnTo', '?series=tatort'));
    });

    test('ignores return values that are not a query string', function () {
        $this->get(route('episodes.edit', ['episode' => $this->episode, 'return_to' => 'https://example.org']))
            ->assertInertia(fn (Assert $page) => $page->where('returnTo', ''));
    });

    test('create page renders without episode', function () {
        $this->get(route('episodes.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Episodes/Edit')->where('episode', null));
    });

    test('guests are redirected to login', function () {
        auth()->logout();

        $this->get(route('episodes.edit', $this->episode))->assertRedirect(route('login'));
        $this->get(route('episodes.create'))->assertRedirect(route('login'));
    });
});

describe('update', function () {
    test('changed fields are saved and protected', function () {
        $other = Team::factory()->for($this->tatort)->create();

        $this->put(route('episodes.update', $this->episode), formData([
            'title' => 'Mein Titel', 'first_aired' => '2002-10-21', 'broadcaster' => 'WDR/NDR', 'team_id' => $other->id, 'number' => 43,
        ]))->assertRedirect(route('episodes.index'))->assertSessionHas('success');

        $episode = $this->episode->fresh();
        expect($episode->title)->toBe('Mein Titel')
            ->and($episode->first_aired->toDateString())->toBe('2002-10-21')
            ->and($episode->broadcaster)->toBe('WDR/NDR')
            ->and($episode->team_id)->toBe($other->id)
            ->and($episode->number)->toBe(43)
            ->and($episode->sort_key)->toBe(4300)
            ->and($episode->manual_fields)->toEqualCanonicalizing(['title', 'first_aired', 'broadcaster', 'team_id', 'number']);
    });

    test('only actually changed fields are protected', function () {
        $this->put(route('episodes.update', $this->episode), formData(['title' => 'Mein Titel']));

        expect($this->episode->fresh()->manual_fields)->toBe(['title']);
    });

    test('saving without changes protects nothing', function () {
        $this->put(route('episodes.update', $this->episode), formData());

        expect($this->episode->fresh()->manual_fields)->toBe([]);
    });

    test('existing protection is kept', function () {
        $this->episode->update(['manual_fields' => ['team_id']]);

        $this->put(route('episodes.update', $this->episode), formData(['title' => 'Neu']));

        expect($this->episode->fresh()->manual_fields)->toEqualCanonicalizing(['team_id', 'title']);
    });

    test('seen fields are never protected', function () {
        $this->put(route('episodes.update', $this->episode), formData(['seen' => true, 'seen_at' => '2026-11-01']));

        $episode = $this->episode->fresh();
        expect($episode->seen)->toBeTrue()
            ->and($episode->seen_at->toDateString())->toBe('2026-11-01')
            ->and($episode->manual_fields)->toBe([]);
    });

    test('not seen clears the seen date', function () {
        $this->episode->update(['seen' => true, 'seen_at' => '2025-01-01']);

        $this->put(route('episodes.update', $this->episode), formData(['seen' => false, 'seen_at' => '2025-01-01']));

        expect($this->episode->fresh()->seen_at)->toBeNull();
    });

    test('empty broadcaster is stored as null', function () {
        $this->put(route('episodes.update', $this->episode), formData(['broadcaster' => '  ']));

        expect($this->episode->fresh()->broadcaster)->toBeNull()
            ->and($this->episode->fresh()->manual_fields)->toBe(['broadcaster']);
    });

    test('redirects back to the list with its filters', function () {
        $this->put(route('episodes.update', $this->episode), formData(['return_to' => '?series=tatort&page=3']))
            ->assertRedirect(route('episodes.index').'?series=tatort&page=3');
    });

    test('foreign redirect targets are ignored', function () {
        $this->put(route('episodes.update', $this->episode), formData(['return_to' => '//evil.example']))
            ->assertRedirect(route('episodes.index'));
    });

    test('series cannot be changed', function () {
        $this->put(route('episodes.update', $this->episode), formData(['series_id' => $this->polizeiruf->id]))
            ->assertSessionHasErrors('series_id');
    });

    test('a film without number may stay without number', function () {
        $film = Episode::factory()->for($this->polizeiruf)->unnumbered(after: 59)->create(['title' => 'Einzelfilm']);

        $this->put(route('episodes.update', $film), [
            'number' => null, 'title' => 'Einzelfilm (korrigiert)', 'first_aired' => $film->first_aired->toDateString(),
            'broadcaster' => null, 'team_id' => null, 'seen' => false,
        ])->assertSessionHasNoErrors();

        expect($film->fresh()->number)->toBeNull()
            ->and($film->fresh()->sort_key)->toBe(5901);
    });
});

describe('validation', function () {
    test('invalid input is rejected', function (array $changes, string $field) {
        $this->put(route('episodes.update', $this->episode), formData($changes))->assertSessionHasErrors($field);

        expect($this->episode->fresh()->title)->toBe('Wikipedia-Titel');
    })->with([
        'empty title' => [['title' => ''], 'title'],
        'title too long' => [['title' => str_repeat('x', 256)], 'title'],
        'number missing' => [['number' => null], 'number'],
        'number not positive' => [['number' => 0], 'number'],
        'invalid date' => [['first_aired' => '20.10.2002'], 'first_aired'],
        'seen in future' => [['seen' => true, 'seen_at' => '2026-11-07'], 'seen_at'],
        'unknown team' => [['team_id' => 999999], 'team_id'],
    ]);

    test('number must be unique within the series', function () {
        Episode::factory()->for($this->tatort)->create(['number' => 43]);
        Episode::factory()->for($this->polizeiruf)->create(['number' => 44]);

        $this->put(route('episodes.update', $this->episode), formData(['number' => 43]))->assertSessionHasErrors('number');
        $this->put(route('episodes.update', $this->episode), formData(['number' => 44]))->assertSessionHasNoErrors();
    });

    test('team must belong to the series of the episode', function () {
        $foreign = Team::factory()->for($this->polizeiruf)->create();

        $this->put(route('episodes.update', $this->episode), formData(['team_id' => $foreign->id]))
            ->assertSessionHasErrors('team_id');
    });

    test('announced episodes cannot be seen', function (?string $firstAired) {
        $this->put(route('episodes.update', $this->episode), formData(['first_aired' => $firstAired, 'seen' => true]))
            ->assertSessionHasErrors('seen');
    })->with(['no date' => [null], 'future' => ['2026-12-24']]);

    test('error messages are German', function () {
        $this->put(route('episodes.update', $this->episode), formData(['title' => '']))
            ->assertSessionHasErrors(['title' => 'Titel muss ausgefüllt werden.']);
    });
});

describe('reset protection', function () {
    test('removes a single field from the protection', function () {
        $this->episode->update(['title' => 'Mein Titel', 'manual_fields' => ['title', 'team_id']]);

        $this->from(route('episodes.edit', $this->episode))
            ->delete(route('episodes.protection.destroy', [$this->episode, 'title']))
            ->assertRedirect(route('episodes.edit', $this->episode))
            ->assertSessionHas('success');

        $episode = $this->episode->fresh();
        expect($episode->manual_fields)->toBe(['team_id'])
            ->and($episode->title)->toBe('Mein Titel'); // Wikipedia-Wert kommt erst mit dem nächsten Import
    });

    test('next import takes the wikipedia value again', function () {
        $this->episode->update(['title' => 'Mein Titel', 'manual_fields' => ['title']]);
        $this->delete(route('episodes.protection.destroy', [$this->episode, 'title']));

        $page = "=== Ausgestrahlte Folgen ===\n{|\n! Folge !! Titel !! Erstausstrahlung !! Ermittler\n"
            ."|-\n| 42 || [[Tatort: Wikipedia-Titel|Wikipedia-Titel]] || {{DatumZelle|2002-10-20}} || T\n|}\n"
            ."=== Bisher noch nicht im Fernsehen ausgestrahlte Folgen ===\n{|\n! Folge !! Titel !! Erstausstrahlung !! Ermittler\n|}";
        fakeWikipedia(['Liste der Tatort-Folgen' => $page]);
        $this->artisan('episodes:import', ['series' => 'tatort']);

        expect($this->episode->fresh()->title)->toBe('Wikipedia-Titel');
    });

    test('unknown fields are rejected', function () {
        $this->delete(route('episodes.protection.destroy', [$this->episode, 'seen']))->assertNotFound();
    });
});

describe('create', function () {
    test('manually created episode has all fields protected', function () {
        $this->post(route('episodes.store'), [
            'series_id' => $this->tatort->id, 'number' => 1400, 'title' => 'Ganz neu', 'first_aired' => '2026-11-01',
            'broadcaster' => 'NDR', 'team_id' => $this->team->id, 'seen' => true, 'seen_at' => '2026-11-02',
            'return_to' => '?sort=date',
        ])->assertRedirect(route('episodes.index').'?sort=date')->assertSessionHas('success');

        $episode = Episode::where('number', 1400)->first();
        expect($episode->title)->toBe('Ganz neu')
            ->and($episode->series_id)->toBe($this->tatort->id)
            ->and($episode->seen)->toBeTrue()
            ->and($episode->wikipedia_ref)->toBeNull()
            ->and($episode->manual_fields)->toEqualCanonicalizing(Episode::PROTECTABLE_FIELDS);
    });

    test('series is required and must be an imported series', function () {
        $other = Series::factory()->create();

        $this->post(route('episodes.store'), ['number' => 1, 'title' => 'X'])->assertSessionHasErrors('series_id');
        $this->post(route('episodes.store'), ['series_id' => $other->id, 'number' => 1, 'title' => 'X'])
            ->assertSessionHasErrors('series_id');
    });

    test('number must be unique in the chosen series', function () {
        $this->post(route('episodes.store'), ['series_id' => $this->tatort->id, 'number' => 42, 'title' => 'X'])
            ->assertSessionHasErrors('number');
    });
});
