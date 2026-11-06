<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user:create creates a user from options', function () {
    $this->artisan('user:create', [
        '--name' => 'Ami',
        '--email' => 'ami@example.com',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    $user = User::where('email', 'ami@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ami')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

test('user:create prompts for missing values', function () {
    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Ami')
        ->expectsQuestion('E-Mail', 'ami@example.com')
        ->expectsQuestion('Passwort', 'secret-password')
        ->assertSuccessful();

    expect(User::where('email', 'ami@example.com')->exists())->toBeTrue();
});

test('created user can log in', function () {
    $this->artisan('user:create', [
        '--name' => 'Ami',
        '--email' => 'ami@example.com',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    $this->post('/login', [
        'email' => 'ami@example.com',
        'password' => 'secret-password',
    ])->assertRedirect(route('episodes.index', absolute: false));

    $this->assertAuthenticated();
});

test('user:create rejects invalid input', function (array $options) {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', array_merge([
        '--name' => 'Ami',
        '--email' => 'ami@example.com',
        '--password' => 'secret-password',
    ], $options))->assertFailed();

    expect(User::count())->toBe(1);
})->with([
    'duplicate email' => [['--email' => 'taken@example.com']],
    'invalid email' => [['--email' => 'not-an-email']],
    'short password' => [['--password' => 'short']],
    'empty name' => [['--name' => '']],
]);
