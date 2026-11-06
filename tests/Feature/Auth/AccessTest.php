<?php

use App\Models\User;

test('registration routes do not exist', function (string $method, string $uri) {
    $this->call($method, $uri)->assertNotFound();
})->with([
    ['GET', '/register'],
    ['POST', '/register'],
    ['GET', '/forgot-password'],
    ['POST', '/forgot-password'],
    ['GET', '/reset-password/some-token'],
    ['GET', '/verify-email'],
]);

test('guests are redirected to login', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with(['/', '/statistik', '/profile', '/confirm-password']);

test('episode list is the start page for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Episodes/Index'));
});

test('dashboard renders for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/statistik')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('login page does not offer registration or password reset', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->missing('canRegister')
            ->missing('canResetPassword'));
});

test('login is throttled after too many attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Zu viele');
});

test('styleguide is not available outside local development', function () {
    $this->actingAs(User::factory()->create())
        ->get('/styleguide')
        ->assertNotFound();
});

test('statistics page renders', function () {
    $this->actingAs(User::factory()->create())
        ->get('/statistik')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});
