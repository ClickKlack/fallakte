<?php

use App\Enums\EpisodeStatus;
use App\Models\Episode;
use App\Models\Series;
use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

test('manual fields default to an empty list', function () {
    $episode = Episode::create([
        'series_id' => Series::factory()->create()->id,
        'number' => 1,
        'title' => 'Taxi nach Leipzig',
        'first_aired' => '1970-11-29',
    ])->fresh();

    expect($episode->manual_fields)->toBe([])
        ->and($episode->seen)->toBeFalse()
        ->and($episode->seen_at)->toBeNull();
});

test('attributes are cast', function () {
    $episode = Episode::factory()->seen('2025-03-01')->create([
        'first_aired' => '1970-11-29',
        'manual_fields' => ['title'],
    ])->fresh();

    expect($episode->first_aired->toDateString())->toBe('1970-11-29')
        ->and($episode->seen_at->toDateString())->toBe('2025-03-01')
        ->and($episode->seen)->toBeTrue()
        ->and($episode->status)->toBe(EpisodeStatus::Aired)
        ->and($episode->manual_fields)->toBe(['title']);
});

test('status is derived from air date on save', function (?string $firstAired, EpisodeStatus $expected) {
    Carbon::setTestNow('2026-11-06 12:00');

    $episode = Episode::factory()->create(['first_aired' => $firstAired]);

    expect($episode->fresh()->status)->toBe($expected);
})->with([
    'no date' => [null, EpisodeStatus::Announced],
    'future' => ['2026-12-24', EpisodeStatus::Announced],
    'today' => ['2026-11-06', EpisodeStatus::Aired],
    'past' => ['1970-11-29', EpisodeStatus::Aired],
]);

test('status is recalculated when the air date changes', function () {
    Carbon::setTestNow('2026-11-06 12:00');
    $episode = Episode::factory()->withoutDate()->create();

    $episode->update(['first_aired' => '2026-10-01']);

    expect($episode->fresh()->status)->toBe(EpisodeStatus::Aired);
});

test('announced episodes become aired once their date is reached', function () {
    Carbon::setTestNow('2026-11-06 12:00');
    $due = Episode::factory()->create(['first_aired' => '2026-11-08']);
    $later = Episode::factory()->create(['first_aired' => '2026-12-24']);
    $undated = Episode::factory()->withoutDate()->create();

    Carbon::setTestNow('2026-11-08 08:00');

    expect(Episode::promoteAiredAnnouncements())->toBe(1)
        ->and($due->fresh()->status)->toBe(EpisodeStatus::Aired)
        ->and($later->fresh()->status)->toBe(EpisodeStatus::Announced)
        ->and($undated->fresh()->status)->toBe(EpisodeStatus::Announced);
});

test('aired and announced scopes split episodes', function () {
    Episode::factory()->count(3)->create();
    Episode::factory()->announced()->count(2)->create();
    Episode::factory()->withoutDate()->create();

    expect(Episode::aired()->count())->toBe(3)
        ->and(Episode::announced()->count())->toBe(3);
});

test('factory states produce consistent data', function () {
    expect(Episode::factory()->create()->status)->toBe(EpisodeStatus::Aired)
        ->and(Episode::factory()->announced()->create()->status)->toBe(EpisodeStatus::Announced)
        ->and(Episode::factory()->withoutDate()->create()->status)->toBe(EpisodeStatus::Announced);
});
