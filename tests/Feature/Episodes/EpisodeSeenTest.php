<?php

use App\Models\Episode;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-11-06 12:00');
    $this->actingAs(User::factory()->create());
    $this->episode = Episode::factory()->create(['first_aired' => '2000-01-01']);
});

afterEach(fn () => Carbon::setTestNow());

test('episode can be marked as seen with date', function () {
    $this->from('/')->patch(route('episodes.seen', $this->episode), ['seen' => true, 'seen_at' => '2026-11-05'])
        ->assertRedirect('/')
        ->assertSessionHasNoErrors();

    $episode = $this->episode->fresh();
    expect($episode->seen)->toBeTrue()
        ->and($episode->seen_at->toDateString())->toBe('2026-11-05');
});

test('episode can be marked as seen without date', function () {
    $this->patch(route('episodes.seen', $this->episode), ['seen' => true, 'seen_at' => null])->assertSessionHasNoErrors();

    expect($this->episode->fresh()->seen)->toBeTrue()
        ->and($this->episode->fresh()->seen_at)->toBeNull();
});

test('removing the check mark clears the date', function () {
    $this->episode->update(['seen' => true, 'seen_at' => '2025-01-01']);

    $this->patch(route('episodes.seen', $this->episode), ['seen' => false, 'seen_at' => '2025-01-01'])->assertSessionHasNoErrors();

    expect($this->episode->fresh()->seen)->toBeFalse()
        ->and($this->episode->fresh()->seen_at)->toBeNull();
});

test('json requests get the new state', function () {
    $this->patchJson(route('episodes.seen', $this->episode), ['seen' => true, 'seen_at' => '2026-11-06'])
        ->assertOk()
        ->assertExactJson(['seen' => true, 'seen_at' => '2026-11-06']);
});

test('announced episode cannot be marked as seen (422)', function () {
    $announced = Episode::factory()->announced()->create();

    $this->patchJson(route('episodes.seen', $announced), ['seen' => true])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['seen' => 'Angekündigte Folgen können noch nicht als gesehen markiert werden.']);

    expect($announced->fresh()->seen)->toBeFalse();
});

test('announced episode error is shown via session for inertia requests', function () {
    $announced = Episode::factory()->withoutDate()->create();

    $this->from('/')->patch(route('episodes.seen', $announced), ['seen' => true])
        ->assertRedirect('/')
        ->assertSessionHasErrors('seen');
});

test('check mark of an announced episode can be removed', function () {
    $announced = Episode::factory()->announced()->create(['seen' => true]);

    $this->patchJson(route('episodes.seen', $announced), ['seen' => false])->assertOk();

    expect($announced->fresh()->seen)->toBeFalse();
});

test('date is validated', function (mixed $date) {
    $this->patchJson(route('episodes.seen', $this->episode), ['seen' => true, 'seen_at' => $date])
        ->assertStatus(422)
        ->assertJsonValidationErrors('seen_at');

    expect($this->episode->fresh()->seen)->toBeFalse();
})->with([
    'future' => ['2026-11-07'],
    'invalid format' => ['06.11.2026'],
    'impossible date' => ['2026-02-30'],
    'too early' => ['1969-12-31'],
    'not a date' => ['gestern'],
]);

test('today is a valid date', function () {
    $this->patchJson(route('episodes.seen', $this->episode), ['seen' => true, 'seen_at' => '2026-11-06'])->assertOk();
});

test('seen flag is required and must be boolean', function (array $payload) {
    $this->patchJson(route('episodes.seen', $this->episode), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('seen');
})->with([
    'missing' => [[]],
    'not boolean' => [['seen' => 'vielleicht']],
]);

test('marking does not touch other fields', function () {
    $this->episode->update(['manual_fields' => ['title']]);
    $before = $this->episode->fresh()->only(['title', 'number', 'first_aired', 'team_id', 'manual_fields']);

    $this->patchJson(route('episodes.seen', $this->episode), ['seen' => true, 'seen_at' => '2026-11-01']);

    expect($this->episode->fresh()->only(['title', 'number', 'first_aired', 'team_id', 'manual_fields']))->toEqual($before);
});

test('guests cannot mark episodes', function () {
    auth()->logout();

    $this->patch(route('episodes.seen', $this->episode), ['seen' => true])->assertRedirect(route('login'));

    expect($this->episode->fresh()->seen)->toBeFalse();
});

test('unknown episode returns 404', function () {
    $this->patchJson('/episodes/999999/seen', ['seen' => true])->assertNotFound();
});
