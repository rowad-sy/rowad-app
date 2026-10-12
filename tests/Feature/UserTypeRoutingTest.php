<?php

use App\Models\Admin\Permission;
use App\Models\User;

test('user types dictionary drives validation — rowaduna accepted and unknown rejected', function () {
    expect(array_keys(User::TYPES))->toContain('rowaduna');

    $admin = User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'عضو فريق روادنا',
        'email' => 'rw-lead@rowad.app',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'type' => 'rowaduna',
    ])->assertRedirect();

    expect(User::firstWhere('email', 'rw-lead@rowad.app')->type)->toBe('rowaduna')
        ->and(User::firstWhere('email', 'rw-lead@rowad.app')->typeLabel())->toBe('روادنا');

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'غير صالح',
        'email' => 'bad@rowad.app',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'type' => 'not-a-real-type',
    ])->assertSessionHasErrors('type');
});

test('rowaduna user lands on the rowaduna dashboard after login entry point', function () {
    $user = User::factory()->create(['type' => 'rowaduna', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $user->id,
        'model_names' => ['page:admin.rowaduna.dashboard'],
        'can_view' => true,
    ]);

    expect($user->landingRoute())->toBe('/admin/rowaduna');
    $this->actingAs($user)->get('/')->assertRedirect('/admin/rowaduna');
    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/rowaduna');
});

test('rowaduna without dashboard permission lands on workspace instead of 403', function () {
    $user = User::factory()->create(['type' => 'rowaduna', 'must_change_password' => false]);

    expect($user->landingRoute())->toBe('/admin/dashboard');
    $this->actingAs($user)->get('/')->assertRedirect('/admin/dashboard');
});

test('employee and super-admin landings unchanged (null = portal flow)', function () {
    $employee = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    expect($employee->landingRoute())->toBeNull();

    $this->actingAs($employee)->get('/')->assertRedirect(route('admin.portal'));
});
