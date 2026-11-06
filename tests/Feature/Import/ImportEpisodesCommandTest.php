<?php

use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\Series;
use Database\Seeders\SeriesSeeder;

function simplePage(string $section, string $date = '1970-11-29'): string
{
    return "=== {$section} ===\n{|\n! Nr. !! Titel !! Sender !! Erstausstr. !! Ermittler !! Fall !! Autor !! Regie !! Besonderheiten\n"
        ."|-\n| 1 || [[Folge Eins]] || ARD || {{DatumZelle|{$date}}} || Team || 1 || A || R || \n|}\n";
}

beforeEach(function () {
    $this->seed(SeriesSeeder::class);
    fakeWikipedia([
        'Liste der Tatort-Folgen' => str_replace('Nr.', 'Folge', simplePage('Ausgestrahlte Folgen').simplePage('Bisher noch nicht im Fernsehen ausgestrahlte Folgen')),
        'Liste der Polizeiruf-110-Folgen' => simplePage('Gesendete Folgen').simplePage('Noch nicht ausgestrahlte Folgen'),
    ]);
});

test('imports both series without argument', function () {
    $this->artisan('episodes:import')
        ->expectsOutputToContain('Tatort')
        ->expectsOutputToContain('Polizeiruf 110')
        ->assertFailed(); // jeweils doppelte Nummer 1 aus der zweiten Tabelle → Fehler

    expect(Episode::count())->toBe(2)
        ->and(ImportRun::count())->toBe(2);
});

test('imports a single series', function () {
    fakeWikipedia(['Liste der Polizeiruf-110-Folgen' => simplePage('Gesendete Folgen')."=== Noch nicht ausgestrahlte Folgen ===\n{|\n! Nr. !! Titel !! Sender !! Erstausstr. !! Ermittler !! Fall !! Autor !! Regie !! Besonderheiten\n|}\n"]);

    $this->artisan('episodes:import', ['series' => Series::POLIZEIRUF])
        ->expectsOutputToContain('Polizeiruf 110')
        ->assertSuccessful();

    expect(Episode::count())->toBe(1)
        ->and(Episode::first()->series->slug)->toBe(Series::POLIZEIRUF);
});

test('dry run shows changes and writes nothing', function () {
    fakeWikipedia(['Liste der Polizeiruf-110-Folgen' => simplePage('Gesendete Folgen')."=== Noch nicht ausgestrahlte Folgen ===\n{|\n! Nr. !! Titel !! Sender !! Erstausstr. !! Ermittler !! Fall !! Autor !! Regie !! Besonderheiten\n|}\n"]);

    $this->artisan('episodes:import', ['series' => Series::POLIZEIRUF, '--dry-run' => true])
        ->expectsOutputToContain('[Probelauf] Polizeiruf 110')
        ->expectsOutputToContain('neu: 1 „Folge Eins“')
        ->expectsOutputToContain('Probelauf: Es wurde nichts gespeichert.')
        ->assertSuccessful();

    expect(Episode::count())->toBe(0)
        ->and(ImportRun::count())->toBe(0);
});

test('unknown series is rejected', function () {
    $this->artisan('episodes:import', ['series' => 'derrick'])
        ->expectsOutputToContain('Unbekannte Reihe „derrick“')
        ->assertFailed();
});

test('missing seeded series is reported', function () {
    Series::query()->delete();

    $this->artisan('episodes:import', ['series' => Series::TATORT])
        ->expectsOutputToContain('db:seed')
        ->assertFailed();
});
