<?php

use App\Console\ImportSchedule;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Registriert den Import-Zeitplan auf einem frischen Schedule und liefert dessen Einträge.
 *
 * @return array<int, Event>
 */
function importEvents(): array
{
    $schedule = new Schedule;
    ImportSchedule::register($schedule);

    return $schedule->events();
}

test('import runs weekly on monday at 4am berlin time by default', function () {
    $events = importEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]->command)->toContain('episodes:import')
        ->and($events[0]->expression)->toBe('0 4 * * 1')
        ->and($events[0]->timezone)->toBe('Europe/Berlin');
});

test('import schedule can be configured', function () {
    config([
        'services.wikipedia.import_schedule' => '30 2 * * 0',
        'services.wikipedia.import_timezone' => 'UTC',
    ]);

    $events = importEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('30 2 * * 0')
        ->and($events[0]->timezone)->toBe('UTC');
});

test('empty import schedule disables the automatic import', function () {
    config(['services.wikipedia.import_schedule' => '']);

    expect(importEvents())->toBeEmpty();
});

test('invalid import schedule disables the automatic import', function () {
    config(['services.wikipedia.import_schedule' => 'every monday']);

    expect(importEvents())->toBeEmpty();
});

test('import is part of the application schedule', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('episodes:import')
        ->assertSuccessful();
});
