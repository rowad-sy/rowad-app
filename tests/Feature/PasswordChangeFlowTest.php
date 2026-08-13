<?php

use App\Models\User;
use App\Notifications\UserActivationMail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt as LivewireVolt;

test('user with must_change_password is redirected to password change and gets activation email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'must_change_password' => true,
        'activation_email_sent_at' => null,
    ]);

    $response = LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.password.change', absolute: false));

    $this->assertAuthenticated();

    $user->refresh();
    expect($user->must_change_password)->toBeTrue();
    expect($user->activation_email_sent_at)->not->toBeNull();

    Notification::assertSentTo($user, UserActivationMail::class);
});

test('activation email is only sent once', function () {
    Notification::fake();

    $user = User::factory()->create([
        'must_change_password' => true,
        'activation_email_sent_at' => now(),
    ]);

    LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    Notification::assertNotSentTo($user, UserActivationMail::class);
});

test('inactive user is redirected to profile on protected pages', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->get(route('admin.home'))
        ->assertRedirect(route('admin.profile'));
});

test('inactive user can access profile', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->get(route('admin.profile'))->assertStatus(200);
});

test('forced password change updates flag', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
        'is_active' => true,
    ]);

    $this->actingAs($user)->put(route('admin.password.update'), [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('admin.profile'));

    expect($user->fresh()->must_change_password)->toBeFalse();
});

test('clicking activation link activates the account and verifies email', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'must_change_password' => true,
        'email_verified_at' => null,
    ]);

    $url = URL::temporarySignedRoute('activation.verify', now()->addDays(7), ['user' => $user->id]);

    $this->actingAs($user)->get($url)
        ->assertRedirect(route('admin.password.change'));

    $user->refresh();
    expect($user->is_active)->toBeTrue();
    expect($user->hasVerifiedEmail())->toBeTrue();
});

test('activation link is invalid when signature is broken', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->get(route('activation.verify', $user))
        ->assertForbidden();

    expect($user->fresh()->is_active)->toBeFalse();
});

test('cannot activate another users account', function () {
    $owner = User::factory()->create(['is_active' => false]);
    $other = User::factory()->create(['is_active' => false]);

    $url = URL::temporarySignedRoute('activation.verify', now()->addDays(7), ['user' => $owner->id]);

    $this->actingAs($other)->get($url)->assertForbidden();

    expect($owner->fresh()->is_active)->toBeFalse();
});

test('active user who must change password is redirected from protected pages', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => true,
    ]);

    $this->actingAs($user)->get(route('admin.home'))
        ->assertRedirect(route('admin.password.change'));
});

test('inactive user can resend activation email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'is_active' => false,
        'email_verified_at' => null,
        'activation_email_sent_at' => now()->subHour(),
        'activation_email_count' => 1,
    ]);

    $this->actingAs($user)->post(route('activation.resend'))
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->activation_email_count)->toBe(2);

    Notification::assertSentTo($user, UserActivationMail::class);
});

test('resend is blocked within the interval', function () {
    Notification::fake();

    $user = User::factory()->create([
        'is_active' => false,
        'email_verified_at' => null,
        'activation_email_sent_at' => now(),
        'activation_email_count' => 1,
    ]);

    $this->actingAs($user)->post(route('activation.resend'))
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHas('info');

    expect($user->fresh()->activation_email_count)->toBe(1);

    Notification::assertNotSentTo($user, UserActivationMail::class);
});

test('resend is blocked when max limit is reached', function () {
    Notification::fake();

    $user = User::factory()->create([
        'is_active' => false,
        'activation_email_sent_at' => now()->subHour(),
        'activation_email_count' => config('activation.max_resends'),
    ]);

    $this->actingAs($user)->post(route('activation.resend'))
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHas('error');

    expect($user->fresh()->activation_email_count)->toBe(config('activation.max_resends'));

    Notification::assertNotSentTo($user, UserActivationMail::class);
});

test('active user cannot resend activation email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'is_active' => true,
        'activation_email_count' => 0,
    ]);

    $this->actingAs($user)->post(route('activation.resend'))
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHas('error');

    expect($user->fresh()->activation_email_count)->toBe(0);

    Notification::assertNotSentTo($user, UserActivationMail::class);
});

test('deactivated user cannot resend activation email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'is_active' => false,
        'activation_email_count' => 2,
    ]);

    $this->actingAs($user)->post(route('activation.resend'))
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHas('error');

    expect($user->fresh()->activation_email_count)->toBe(2);

    Notification::assertNotSentTo($user, UserActivationMail::class);
});

test('password change resets activation email counter', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'activation_email_count' => 4,
    ]);

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('admin.profile'));

    expect($user->fresh()->activation_email_count)->toBe(0);
});
