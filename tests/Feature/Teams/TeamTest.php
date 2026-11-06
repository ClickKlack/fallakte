<?php

use App\Actions\MergeTeams;
use App\Models\Episode;
use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;
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

/** Team-Eintrag aus den Props der Übersicht. */
function teamProps(int $id): array
{
    $found = null;
    test()->get(route('teams.index'))->assertInertia(function (Assert $page) use ($id, &$found) {
        $found = collect($page->toArray()['props']['teams'])->firstWhere('id', $id);
    });

    return $found;
}

describe('overview', function () {
    test('renders teams with series and options', function () {
        Team::factory()->for($this->tatort)->create(['name' => 'Thiel und Boerne']);

        $this->get(route('teams.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teams/Index')
                ->has('teams', 1)
                ->where('teams.0.name', 'Thiel und Boerne')
                ->where('teams.0.series.slug', 'tatort')
                ->has('series', 2));
    });

    test('progress counts only aired episodes', function () {
        $team = Team::factory()->for($this->tatort)->create();
        Episode::factory()->for($this->tatort)->for($team)->seen()->create(['first_aired' => '2002-10-20']);
        Episode::factory()->for($this->tatort)->for($team)->create(['first_aired' => '2010-05-02']);
        Episode::factory()->for($this->tatort)->for($team)->create(['first_aired' => '2015-01-01']);
        Episode::factory()->for($this->tatort)->for($team)->announced()->create(['first_aired' => '2026-12-24']);
        Episode::factory()->for($this->tatort)->for($team)->withoutDate()->create();

        $props = teamProps($team->id);

        expect($props['aired_count'])->toBe(3)
            ->and($props['seen_count'])->toBe(1)
            ->and($props['episodes_count'])->toBe(5)
            ->and($props['first_year'])->toBe(2002)
            ->and($props['last_year'])->toBe(2015);
    });

    test('team with only announced episodes has no period', function () {
        $team = Team::factory()->for($this->tatort)->create();
        Episode::factory()->for($this->tatort)->for($team)->announced()->create();

        $props = teamProps($team->id);

        expect($props['aired_count'])->toBe(0)
            ->and($props['first_year'])->toBeNull();
    });

    test('aliases are listed', function () {
        $team = Team::factory()->for($this->tatort)->create();
        TeamAlias::create(['team_id' => $team->id, 'raw_value' => 'Thiel und Boerne']);
        TeamAlias::create(['team_id' => $team->id, 'raw_value' => 'Boerne und Thiel']);

        expect(teamProps($team->id)['aliases'])->toBe(['Boerne und Thiel', 'Thiel und Boerne']);
    });

    test('guests are redirected', function () {
        auth()->logout();

        $this->get(route('teams.index'))->assertRedirect(route('login'));
    });
});

describe('update', function () {
    test('name, city and investigators can be changed', function () {
        $team = Team::factory()->for($this->tatort)->create(['name' => 'Thiel und Boerne']);

        $this->from(route('teams.index'))->put(route('teams.update', $team), [
            'name' => 'Münster – Thiel/Boerne', 'city' => ' Münster ', 'investigators' => '',
        ])->assertRedirect(route('teams.index'))->assertSessionHas('success');

        $team->refresh();
        expect($team->name)->toBe('Münster – Thiel/Boerne')
            ->and($team->city)->toBe('Münster')
            ->and($team->investigators)->toBeNull();
    });

    test('renaming keeps the aliases for the import', function () {
        $team = Team::factory()->for($this->tatort)->create(['name' => 'Thiel und Boerne']);
        TeamAlias::create(['team_id' => $team->id, 'raw_value' => 'Thiel und Boerne']);

        $this->put(route('teams.update', $team), ['name' => 'Münster']);

        expect(TeamAlias::where('raw_value', 'Thiel und Boerne')->value('team_id'))->toBe($team->id);
    });

    test('name is required', function () {
        $team = Team::factory()->for($this->tatort)->create();

        $this->put(route('teams.update', $team), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Name muss ausgefüllt werden.']);
    });
});

describe('merge', function () {
    beforeEach(function () {
        $this->target = Team::factory()->for($this->tatort)->create(['name' => 'Borowski und Şahin', 'city' => null]);
        $this->source = Team::factory()->for($this->tatort)->create(['name' => 'Borowski und Sahin', 'city' => 'Kiel']);
        TeamAlias::create(['team_id' => $this->target->id, 'raw_value' => 'Borowski und Şahin']);
        TeamAlias::create(['team_id' => $this->source->id, 'raw_value' => 'Borowski und Sahin']);
        Episode::factory()->for($this->tatort)->for($this->source)->count(3)->create();
        Episode::factory()->for($this->tatort)->for($this->target)->count(2)->create();
    });

    test('moves episodes and aliases and deletes the source team', function () {
        $this->from(route('teams.index'))
            ->post(route('teams.merge', $this->source), ['target_id' => $this->target->id])
            ->assertRedirect(route('teams.index'))
            ->assertSessionHas('success');

        expect(Team::find($this->source->id))->toBeNull()
            ->and($this->target->episodes()->count())->toBe(5)
            ->and($this->target->aliases()->pluck('raw_value')->sort()->values()->all())
            ->toBe(['Borowski und Sahin', 'Borowski und Şahin'])
            ->and($this->target->fresh()->city)->toBe('Kiel'); // fehlende Angaben werden übernommen
    });

    test('success message uses singular and plural', function () {
        $this->post(route('teams.merge', $this->source), ['target_id' => $this->target->id])
            ->assertSessionHas('success', '„Borowski und Sahin“ wurde mit „Borowski und Şahin“ zusammengeführt (3 Folgen).');

        $single = Team::factory()->for($this->tatort)->create(['name' => 'Einzeln']);
        Episode::factory()->for($this->tatort)->for($single)->create();
        $this->post(route('teams.merge', $single), ['target_id' => $this->target->id])
            ->assertSessionHas('success', '„Einzeln“ wurde mit „Borowski und Şahin“ zusammengeführt (1 Folge).');
    });

    test('merging does not mark episodes as manually changed', function () {
        $this->post(route('teams.merge', $this->source), ['target_id' => $this->target->id]);

        expect(Episode::where('team_id', $this->target->id)->pluck('manual_fields')->flatten()->all())->toBe([]);
    });

    test('next import assigns the raw values of both teams to the target team', function () {
        $this->post(route('teams.merge', $this->source), ['target_id' => $this->target->id]);

        $page = "=== Ausgestrahlte Folgen ===\n{|\n! Folge !! Titel !! Erstausstrahlung !! Ermittler\n"
            ."|-\n| 900 || [[Tatort: A|A]] || {{DatumZelle|2020-01-01}} || Borowski und Sahin\n"
            ."|-\n| 901 || [[Tatort: B|B]] || {{DatumZelle|2020-02-01}} || [[Borowski und Şahin]]\n|}\n"
            ."=== Bisher noch nicht im Fernsehen ausgestrahlte Folgen ===\n{|\n! Folge !! Titel !! Erstausstrahlung !! Ermittler\n|}";
        fakeWikipedia(['Liste der Tatort-Folgen' => $page]);
        $this->artisan('episodes:import', ['series' => 'tatort']);

        expect(Episode::whereIn('number', [900, 901])->pluck('team_id')->unique()->all())->toBe([$this->target->id])
            ->and(Team::count())->toBe(1);
    });

    test('merging into a team of another series is rejected', function () {
        $foreign = Team::factory()->for($this->polizeiruf)->create();

        $this->post(route('teams.merge', $this->source), ['target_id' => $foreign->id])
            ->assertSessionHasErrors(['target_id' => 'Teams können nur innerhalb derselben Reihe zusammengeführt werden.']);

        expect(Team::find($this->source->id))->not->toBeNull()
            ->and($this->source->episodes()->count())->toBe(3);
    });

    test('merging a team with itself is rejected', function () {
        $this->post(route('teams.merge', $this->source), ['target_id' => $this->source->id])
            ->assertSessionHasErrors('target_id');

        expect(Team::find($this->source->id))->not->toBeNull();
    });

    test('target is required and must exist', function (mixed $target) {
        $this->post(route('teams.merge', $this->source), ['target_id' => $target])->assertSessionHasErrors('target_id');
    })->with([[null], [999999]]);

    test('merge action rolls back completely on error', function () {
        Team::deleting(fn () => throw new RuntimeException('Simulierter Fehler'));

        expect(fn () => app(MergeTeams::class)($this->source, $this->target))->toThrow(RuntimeException::class);

        expect($this->source->episodes()->count())->toBe(3)
            ->and($this->source->aliases()->count())->toBe(1);
    });
});
