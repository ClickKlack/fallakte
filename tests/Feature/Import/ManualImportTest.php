<?php

use App\Import\ImportLock;
use App\Models\Episode;
use App\Models\ImportRun;
use App\Models\User;
use Database\Seeders\SeriesSeeder;
use Illuminate\Support\Facades\RateLimiter;

function manualPage(string $section, string $announced, string $number = 'Nr.'): string
{
    $header = "{|\n! {$number} !! Titel !! Erstausstr. !! Ermittler\n";

    return "=== {$section} ===\n{$header}|-\n| 1 || [[Folge Eins]] || {{DatumZelle|2000-01-01}} || Team\n|}\n"
        ."=== {$announced} ===\n{$header}|}\n";
}

beforeEach(function () {
    $this->seed(SeriesSeeder::class);
    RateLimiter::clear('fallakte:manual-import');
    fakeWikipedia([
        'Liste der Tatort-Folgen' => manualPage('Ausgestrahlte Folgen', 'Bisher noch nicht im Fernsehen ausgestrahlte Folgen', 'Folge'),
        'Liste der Polizeiruf-110-Folgen' => manualPage('Gesendete Folgen', 'Noch nicht ausgestrahlte Folgen'),
    ]);
    $this->actingAs(User::factory()->create());
});

test('imports both series and reports the result', function () {
    $this->from(route('about'))->post(route('import.run'))
        ->assertRedirect(route('about'))
        ->assertSessionHas('success', 'Import abgeschlossen. Tatort: 1 neu, 0 aktualisiert, 0 geschützt. Polizeiruf 110: 1 neu, 0 aktualisiert, 0 geschützt.');

    expect(Episode::count())->toBe(2)
        ->and(ImportRun::count())->toBe(2);
});

test('errors are reported as error message', function () {
    fakeWikipedia(['Liste der Tatort-Folgen' => 503, 'Liste der Polizeiruf-110-Folgen' => 503]);

    $this->post(route('import.run'))
        ->assertSessionHas('error', fn ($message) => str_starts_with($message, 'Import mit 2 Fehler(n) abgeschlossen'));
});

test('a running import blocks a second one', function () {
    $lock = ImportLock::make();
    $lock->get();

    $this->post(route('import.run'))->assertSessionHas('error', 'Es läuft bereits ein Import. Bitte gleich noch einmal versuchen.');

    expect(Episode::count())->toBe(0);
    $lock->release();
});

test('imports via the app are limited to one per five minutes', function () {
    $this->post(route('import.run'))->assertSessionHas('success');

    $this->post(route('import.run'))
        ->assertSessionHas('error', 'Der letzte Import war gerade erst. Ein neuer ist in 5 Minute(n) möglich.');

    $this->travel(301)->seconds();
    $this->post(route('import.run'))->assertSessionHas('success');
});

test('the lock is released after the import', function () {
    $this->post(route('import.run'));

    $lock = ImportLock::make();
    expect($lock->get())->toBeTrue();
    $lock->release();
});

test('the artisan command respects the same lock', function () {
    $lock = ImportLock::make();
    $lock->get();

    $this->artisan('episodes:import')
        ->expectsOutputToContain('Es läuft bereits ein Import')
        ->assertFailed();

    $lock->release();
});

test('guests cannot start an import', function () {
    auth()->logout();

    $this->post(route('import.run'))->assertRedirect(route('login'));
    expect(ImportRun::count())->toBe(0);
});
