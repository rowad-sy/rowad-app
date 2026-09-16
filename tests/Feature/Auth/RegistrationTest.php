<?php

use Livewire\Volt\Volt;

test('registration is not publicly available and redirects guests to login', function () {
    // Registration is admin-managed; guests are sent to the unified login page.
    $response = $this->get('/register');

    $response->assertRedirect('/login');
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});