<?php

use App\Enums\EpisodeStatus;
use Illuminate\Support\Carbon;

$today = Carbon::parse('2026-11-06 12:00');

test('episode without air date is announced', function () use ($today) {
    expect(EpisodeStatus::fromAirDate(null, $today))->toBe(EpisodeStatus::Announced);
});

test('episode with future air date is announced', function (string $date) use ($today) {
    expect(EpisodeStatus::fromAirDate(Carbon::parse($date), $today))->toBe(EpisodeStatus::Announced);
})->with(['2026-11-07', '2027-01-01']);

test('episode with past air date or today is aired', function (string $date) use ($today) {
    expect(EpisodeStatus::fromAirDate(Carbon::parse($date), $today))->toBe(EpisodeStatus::Aired);
})->with(['1970-11-29', '2026-11-05', '2026-11-06', '2026-11-06 23:59']);

test('time of day of today does not matter', function () {
    $lateEvening = Carbon::parse('2026-11-06 23:59');

    expect(EpisodeStatus::fromAirDate(Carbon::parse('2026-11-06 00:00'), $lateEvening))->toBe(EpisodeStatus::Aired)
        ->and(EpisodeStatus::fromAirDate(Carbon::parse('2026-11-07 00:00'), $lateEvening))->toBe(EpisodeStatus::Announced);
});

test('enum values are stable', function () {
    expect(EpisodeStatus::Aired->value)->toBe('aired')
        ->and(EpisodeStatus::Announced->value)->toBe('announced');
});
