<?php

use App\Enums\EpisodeStatus;
use App\Import\EpisodeImporter;
use App\Import\ImportResult;
use App\Import\Wikipedia\Parsers\PolizeirufParser;
use App\Import\Wikipedia\Parsers\TatortParser;
use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use App\Models\Team;
use App\Models\TeamAlias;
use App\Models\User;
use Database\Seeders\SeriesSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Baut eine minimale Tatort-Seite. Zeilen: [Nr, Titel, Datum (Y-m-d|''), Ermittler-Wikitext, optional Sender].
 *
 * @param  list<array{0: int|string, 1: string, 2: string, 3: string}>  $rows
 */
function tatortPage(array $rows): string
{
    $table = fn (array $rows) => "{|\n! Folge !! Titel !! Sender !! Erstausstrahlung !! Ermittler !! Fall !! Autor !! Regie !! Besonderheiten\n"
        .implode('', array_map(fn ($r) => "|-\n| {$r[0]} || [[Tatort: {$r[1]}|{$r[1]}]] || ".($r[4] ?? 'ARD').' || '
            .($r[2] === '' ? '' : "{{DatumZelle|{$r[2]}}}")." || {$r[3]} || 1 || A || R || \n", $rows))
        ."|}\n";

    return "=== Ausgestrahlte Folgen ===\n".$table($rows)
        ."=== Bisher noch nicht im Fernsehen ausgestrahlte Folgen ===\n".$table([]);
}

function importTatort(string $wikitext, bool $dryRun = false): ImportResult
{
    fakeWikipedia(['Liste der Tatort-Folgen' => $wikitext]);

    return app(EpisodeImporter::class)->import(Series::where('slug', Series::TATORT)->first(), new TatortParser, $dryRun);
}

beforeEach(function () {
    Carbon::setTestNow('2026-11-06 12:00');
    $this->seed(SeriesSeeder::class);
});

afterEach(fn () => Carbon::setTestNow());

test('new episodes are created with team and alias', function () {
    $result = importTatort(tatortPage([
        [1, 'Taxi nach Leipzig', '1970-11-29', '[[Paul Trimmel|Trimmel]]'],
        [2, 'Saarbrücken', '1970-12-13', '[[Liersdahl und Schäfermann]]'],
        [3, 'Wieder Trimmel', '1971-01-10', '[[Paul Trimmel|Trimmel]]'],
    ]));

    expect($result->created)->toBe(3)
        ->and($result->updated)->toBe(0)
        ->and($result->teamsCreated)->toBe(2)
        ->and($result->errors)->toBe([]);

    $first = Episode::where('number', 1)->first();
    expect($first->title)->toBe('Taxi nach Leipzig')
        ->and($first->first_aired->toDateString())->toBe('1970-11-29')
        ->and($first->status)->toBe(EpisodeStatus::Aired)
        ->and($first->team->name)->toBe('Trimmel')
        ->and($first->broadcaster)->toBe('ARD')
        ->and($first->wikipedia_ref)->toBe('Tatort: Taxi nach Leipzig')
        ->and($first->sort_key)->toBe(100)
        ->and($first->manual_fields)->toBe([])
        ->and(Episode::where('number', 3)->first()->team_id)->toBe($first->team_id)
        ->and(TeamAlias::where('raw_value', 'Trimmel')->first()->team_id)->toBe($first->team_id);
});

test('running the same import twice changes nothing', function () {
    $page = tatortPage([[1, 'Taxi nach Leipzig', '1970-11-29', 'Trimmel']]);
    importTatort($page);

    $result = importTatort($page);

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0)
        ->and(Episode::count())->toBe(1)
        ->and(Team::count())->toBe(1);
});

test('changed episodes are updated', function () {
    importTatort(tatortPage([[1, 'Alter Titel', '1970-11-29', 'Trimmel']]));

    $result = importTatort(tatortPage([[1, 'Neuer Titel', '1970-11-30', 'Finke']]));

    $episode = Episode::first();
    expect($result->updated)->toBe(1)
        ->and($episode->title)->toBe('Neuer Titel')
        ->and($episode->first_aired->toDateString())->toBe('1970-11-30')
        ->and($episode->team->name)->toBe('Finke')
        ->and($result->changes)->toContain('aktualisiert (Titel, Datum, Team, Quelle): 1 „Neuer Titel“');
});

test('protected fields keep the manual correction', function () {
    importTatort(tatortPage([[1, 'Wikipedia-Titel', '1970-11-29', 'Trimmel']]));
    Episode::first()->update(['title' => 'Mein Titel', 'manual_fields' => ['title']]);

    $result = importTatort(tatortPage([[1, 'Geänderter Wikipedia-Titel', '1970-11-29', 'Trimmel']]));

    expect(Episode::first()->title)->toBe('Mein Titel')
        ->and($result->skipped)->toBe(1)
        ->and($result->updated)->toBe(1) // nur die Quelle (wikipedia_ref) hat sich geändert
        ->and($result->changes)->toContain('geschützt (manuelle Korrektur beibehalten): 1 „Geänderter Wikipedia-Titel“');
});

test('unprotected fields are still updated when another field is protected', function () {
    importTatort(tatortPage([[1, 'Titel', '1970-11-29', 'Trimmel']]));
    Episode::first()->update(['title' => 'Mein Titel', 'manual_fields' => ['title']]);

    importTatort(tatortPage([[1, 'Anderer Titel', '1971-05-05', 'Trimmel']]));

    $episode = Episode::first();
    expect($episode->title)->toBe('Mein Titel')
        ->and($episode->first_aired->toDateString())->toBe('1971-05-05');
});

test('broadcaster is imported, updated and protectable', function () {
    importTatort(tatortPage([[1, 'A', '1970-11-29', 'Trimmel', 'NDR'], [2, 'B', '1970-12-13', 'Trimmel', 'SR']]));
    Episode::where('number', 2)->first()->update(['broadcaster' => 'SR/WDR', 'manual_fields' => ['broadcaster']]);

    $result = importTatort(tatortPage([[1, 'A', '1970-11-29', 'Trimmel', '[[Westdeutscher Rundfunk|WDR]]'], [2, 'B', '1970-12-13', 'Trimmel', 'SR']]));

    expect(Episode::where('number', 1)->first()->broadcaster)->toBe('WDR')
        ->and(Episode::where('number', 2)->first()->broadcaster)->toBe('SR/WDR')
        ->and($result->changes)->toContain('aktualisiert (Sender): 1 „A“')
        ->and($result->skipped)->toBe(1);
});

test('episodes sharing the same wikipedia article are not mixed up', function () {
    $page = "=== Ausgestrahlte Folgen ===\n{|\n! Folge !! Titel !! Erstausstrahlung !! Ermittler\n"
        ."|-\n| 1 || [[Tatort: Doppel|Doppel (1)]] || {{DatumZelle|2000-01-01}} || T\n"
        ."|-\n| 2 || [[Tatort: Doppel|Doppel (2)]] || {{DatumZelle|2000-01-08}} || T\n|}\n"
        ."=== Bisher noch nicht im Fernsehen ausgestrahlte Folgen ===\n{|\n! Folge !! Titel !! Erstausstrahlung !! Ermittler\n|}";

    importTatort($page);
    $second = importTatort($page);

    expect(Episode::orderBy('number')->pluck('title', 'number')->all())->toBe([1 => 'Doppel (1)', 2 => 'Doppel (2)'])
        ->and($second->updated)->toBe(0);
});

test('protected team and date are kept', function () {
    importTatort(tatortPage([[1, 'Titel', '1970-11-29', 'Trimmel']]));
    $myTeam = Team::factory()->for(Series::where('slug', Series::TATORT)->first())->create(['name' => 'Mein Team']);
    Episode::first()->update(['team_id' => $myTeam->id, 'first_aired' => '1970-12-01', 'manual_fields' => ['team_id', 'first_aired']]);

    $result = importTatort(tatortPage([[1, 'Titel', '1970-11-29', 'Trimmel']]));

    $episode = Episode::first();
    expect($episode->team_id)->toBe($myTeam->id)
        ->and($episode->first_aired->toDateString())->toBe('1970-12-01')
        ->and($result->skipped)->toBe(1)
        ->and($result->updated)->toBe(0);
});

test('seen and seen_at are never touched', function () {
    importTatort(tatortPage([
        [1, 'Gesehen mit Datum', '1970-11-29', 'Trimmel'],
        [2, 'Gesehen ohne Datum', '1970-12-13', 'Trimmel'],
    ]));
    Episode::where('number', 1)->update(['seen' => true, 'seen_at' => '2025-01-01']);
    Episode::where('number', 2)->update(['seen' => true]);

    importTatort(tatortPage([
        [1, 'Neuer Titel 1', '1970-11-30', 'Finke'],
        [2, 'Neuer Titel 2', '1970-12-14', 'Finke'],
    ]));

    $one = Episode::where('number', 1)->first();
    $two = Episode::where('number', 2)->first();
    expect($one->title)->toBe('Neuer Titel 1')
        ->and($one->seen)->toBeTrue()
        ->and($one->seen_at->toDateString())->toBe('2025-01-01')
        ->and($two->seen)->toBeTrue()
        ->and($two->seen_at)->toBeNull();
});

test('unknown raw value creates team and alias, known alias resolves to its team', function () {
    $series = Series::where('slug', Series::TATORT)->first();
    $merged = Team::factory()->for($series)->create(['name' => 'Münster – Thiel/Boerne']);
    TeamAlias::create(['team_id' => $merged->id, 'raw_value' => 'Thiel und Boerne']);
    TeamAlias::create(['team_id' => $merged->id, 'raw_value' => 'Boerne und Thiel']);

    $result = importTatort(tatortPage([
        [1, 'A', '2002-10-20', '[[Thiel und Boerne]]'],
        [2, 'B', '2003-10-20', 'Boerne und Thiel'],
        [3, 'C', '2004-10-20', '[[Neues Team]]'],
    ]));

    expect(Episode::where('number', 1)->first()->team_id)->toBe($merged->id)
        ->and(Episode::where('number', 2)->first()->team_id)->toBe($merged->id)
        ->and($result->teamsCreated)->toBe(1)
        ->and(Team::where('name', 'Neues Team')->first()->aliases->pluck('raw_value')->all())->toBe(['Neues Team']);
});

test('aliases of the other series are not used', function () {
    $polizeiruf = Series::where('slug', Series::POLIZEIRUF)->first();
    TeamAlias::create(['team_id' => Team::factory()->for($polizeiruf)->create()->id, 'raw_value' => 'Schmidt']);

    importTatort(tatortPage([[1, 'A', '1980-01-01', 'Schmidt']]));

    expect(Episode::first()->team->series_id)->toBe(Series::where('slug', Series::TATORT)->first()->id);
});

test('episodes without date or with future date are announced, past ones become aired', function () {
    importTatort(tatortPage([
        [1, 'Ohne Datum', '', 'Trimmel'],
        [2, 'Zukunft', '2026-11-08', 'Trimmel'],
        [3, 'Vergangenheit', '2026-11-01', 'Trimmel'],
    ]));

    expect(Episode::where('number', 1)->first()->status)->toBe(EpisodeStatus::Announced)
        ->and(Episode::where('number', 2)->first()->status)->toBe(EpisodeStatus::Announced)
        ->and(Episode::where('number', 3)->first()->status)->toBe(EpisodeStatus::Aired);

    Carbon::setTestNow('2026-11-09 08:00');
    $result = importTatort(tatortPage([
        [1, 'Ohne Datum', '', 'Trimmel'],
        [2, 'Zukunft', '2026-11-08', 'Trimmel'],
        [3, 'Vergangenheit', '2026-11-01', 'Trimmel'],
    ]));

    expect($result->promoted)->toBe(1)
        ->and(Episode::where('number', 2)->first()->status)->toBe(EpisodeStatus::Aired);
});

test('manually renumbered episode is found again via its wikipedia reference', function () {
    importTatort(tatortPage([[5, 'Titel', '1980-01-01', 'Trimmel']]));
    Episode::first()->update(['number' => 6, 'manual_fields' => ['number']]);

    $result = importTatort(tatortPage([[5, 'Titel', '1980-01-01', 'Trimmel']]));

    expect(Episode::count())->toBe(1)
        ->and(Episode::first()->number)->toBe(6)
        ->and($result->skipped)->toBe(1)
        ->and($result->created)->toBe(0);
});

test('manually created episode is matched by number and stays fully protected', function () {
    $series = Series::where('slug', Series::TATORT)->first();
    Episode::factory()->for($series)->create([
        'number' => 7, 'title' => 'Selbst angelegt', 'first_aired' => '2026-10-01',
        'manual_fields' => Episode::PROTECTABLE_FIELDS,
    ]);

    $result = importTatort(tatortPage([[7, 'Wikipedia-Titel', '2026-10-02', 'Trimmel']]));

    $episode = Episode::first();
    expect(Episode::count())->toBe(1)
        ->and($episode->title)->toBe('Selbst angelegt')
        ->and($episode->first_aired->toDateString())->toBe('2026-10-01')
        ->and($episode->team_id)->toBeNull()
        ->and($episode->wikipedia_ref)->toBe('Tatort: Wikipedia-Titel')
        ->and($result->skipped)->toBe(1);
});

test('films without number get a substitute number and are matched by reference', function () {
    fakeWikipedia(['Liste der Polizeiruf-110-Folgen' => wikiFixture('edge-cases-polizeiruf-110.wiki')]);
    $series = Series::where('slug', Series::POLIZEIRUF)->first();

    app(EpisodeImporter::class)->import($series, new PolizeirufParser);
    $second = app(EpisodeImporter::class)->import($series, new PolizeirufParser);

    $film = Episode::whereNull('number')->first();
    expect(Episode::count())->toBe(6)
        ->and($film->title)->toBe('Gelb ist nicht nur die Farbe der Sonne')
        ->and($film->sort_key)->toBe(5901)
        ->and($film->display_number)->toBe('59a')
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and(Episode::orderBy('sort_key')->get()->pluck('display_number')->all())->toBe(['59', '59a', '60', '61', '156', '428']);
});

test('dry run writes nothing but reports changes', function () {
    $result = importTatort(tatortPage([[1, 'Taxi nach Leipzig', '1970-11-29', 'Trimmel']]), dryRun: true);

    expect($result->created)->toBe(1)
        ->and($result->teamsCreated)->toBe(1)
        ->and($result->changes)->toBe(['neu: 1 „Taxi nach Leipzig“'])
        ->and(Episode::count())->toBe(0)
        ->and(Team::count())->toBe(0)
        ->and(TeamAlias::count())->toBe(0)
        ->and(ImportRun::count())->toBe(0);
});

test('dry run does not change existing episodes', function () {
    importTatort(tatortPage([[1, 'Alt', '1970-11-29', 'Trimmel']]));

    $result = importTatort(tatortPage([[1, 'Neu', '1970-11-29', 'Trimmel']]), dryRun: true);

    expect($result->updated)->toBe(1)
        ->and(Episode::first()->title)->toBe('Alt')
        ->and(ImportRun::count())->toBe(1);
});

test('import run is logged', function () {
    importTatort(tatortPage([[1, 'A', '1970-11-29', 'Trimmel']]));
    Episode::first()->update(['title' => 'Mein', 'manual_fields' => ['title']]);

    importTatort(tatortPage([
        [1, 'B', '1970-11-29', 'Trimmel'],
        [2, 'Neu', '1970-12-01', 'Trimmel'],
        ['11a', 'Kaputt', '1970-12-02', 'Trimmel'],
    ]));

    $run = ImportRun::latest('id')->first();
    expect(ImportRun::count())->toBe(2)
        ->and($run->series->slug)->toBe(Series::TATORT)
        ->and($run->started_at)->not->toBeNull()
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->created_count)->toBe(1)
        ->and($run->updated_count)->toBe(1)
        ->and($run->skipped_count)->toBe(1)
        ->and($run->error_count)->toBe(1)
        ->and($run->errors)->toContain('11a');
});

test('http failure is logged and changes nothing', function () {
    fakeWikipedia(['Liste der Tatort-Folgen' => 503]);

    $result = app(EpisodeImporter::class)->import(Series::where('slug', Series::TATORT)->first(), new TatortParser);

    $run = ImportRun::first();
    expect($result->errors[0])->toContain('HTTP 503')
        ->and(Episode::count())->toBe(0)
        ->and($run->error_count)->toBe(1)
        ->and($run->finished_at)->not->toBeNull();
});

test('a page without episodes aborts the import', function () {
    $result = importTatort("== Ganz andere Seite ==\nText");

    expect($result->created)->toBe(0)
        ->and($result->errors)->toContain('Keine Folgen gefunden – Import abgebrochen, es wurde nichts geändert.');
});

test('user agent contains the email address of the user as contact', function () {
    User::factory()->create(['email' => 'ami@example.org']);

    importTatort(tatortPage([[1, 'A', '1970-11-29', 'Trimmel']]));

    Http::assertSent(fn (Request $request) => $request->hasHeader(
        'User-Agent', 'fallakte/1.0 (+https://github.com/ClickKlack/fallakte; ami@example.org)',
    ));
});

test('user agent falls back to the project identification without user', function () {
    importTatort(tatortPage([[1, 'A', '1970-11-29', 'Trimmel']]));

    Http::assertSent(fn (Request $request) => $request->hasHeader(
        'User-Agent', 'fallakte/1.0 (+https://github.com/ClickKlack/fallakte)',
    ));
});

test('request uses the configured user agent and api parameters', function () {
    User::factory()->create(['email' => 'ami@example.org']);
    config(['services.wikipedia.user_agent' => 'fallakte-test/1.0 (test@example.org)']);

    importTatort(tatortPage([[1, 'A', '1970-11-29', 'Trimmel']]));

    Http::assertSent(fn (Request $request) => $request->hasHeader('User-Agent', 'fallakte-test/1.0 (test@example.org)')
        && $request['action'] === 'query'
        && $request['titles'] === 'Liste der Tatort-Folgen'
        && $request['rvslots'] === 'main');
});

test('full snapshots of both lists are imported completely', function () {
    fakeWikipedia([
        'Liste der Tatort-Folgen' => wikiFixture('tatort.wiki'),
        'Liste der Polizeiruf-110-Folgen' => wikiFixture('polizeiruf-110.wiki'),
    ]);

    $tatort = app(EpisodeImporter::class)->import(Series::where('slug', Series::TATORT)->first(), new TatortParser);
    $polizeiruf = app(EpisodeImporter::class)->import(Series::where('slug', Series::POLIZEIRUF)->first(), new PolizeirufParser);

    expect($tatort->errors)->toBe([])
        ->and($polizeiruf->errors)->toBe([])
        ->and($tatort->created)->toBe(1350)
        ->and($polizeiruf->created)->toBe(435)
        ->and(Episode::whereNull('team_id')->count())->toBe(0)
        ->and(Episode::whereNull('title')->orWhere('title', '')->count())->toBe(0)
        ->and(Episode::where('series_id', Series::where('slug', Series::TATORT)->first()->id)->whereNull('first_aired')->count())->toBe(0);
});
