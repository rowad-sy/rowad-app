<?php

use App\Models\Admin\Center;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\Admin\Physiotherapy\PhysioRoom;
use App\Models\User;

function physioSuperAdmin(): User
{
    return User::factory()->create([
        'type' => 'super-admin',
        'must_change_password' => false,
    ]);
}

it('renders all physiotherapy pages for super admin', function () {
    $user = physioSuperAdmin();

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.patients.index'))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.patients.create'))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.rooms.index'))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.rooms.create'))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.followups.index'))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.transfers.index'))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.statistics.index'))->assertStatus(200);
});

it('creates and shows a patient with a session and transfers list', function () {
    $user = physioSuperAdmin();

    $center = Center::first() ?? Center::create(['name' => 'Smoke Center']);
    $room = PhysioRoom::create(['center_id' => $center->id, 'name' => 'غرفة أطفال']);
    $patient = PhysioPatient::create([
        'center_id' => $center->id,
        'name' => 'مريض نقل تجريبي',
        'gender' => 'female',
        'registration_date' => now()->toDateString(),
        'room_id' => $room->id,
        'therapist_id' => $user->id,
        'is_transferred' => 1,
        'transferred_at' => now()->toDateString(),
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.patients.show', $patient))->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.physiotherapy.transfers.index'))->assertStatus(200)->assertSee('مريض نقل تجريبي');

    $this->actingAs($user)
        ->post(route('admin.physiotherapy.sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => now()->toDateString(),
            'what_done' => 'جلسة كهرباء',
        ])->assertSessionHas('success');

    $this->assertDatabaseHas('physio_sessions', ['patient_id' => $patient->id, 'what_done' => 'جلسة كهرباء']);

    $patient->delete();
    $room->delete();
});

it('renders the four help pages', function () {
    $user = physioSuperAdmin();

    $this->actingAs($user)->get(route('admin.movement-plans.help'))->assertStatus(200);
    $this->actingAs($user)->get(route('admin.media-plans.help'))->assertStatus(200);
    $this->actingAs($user)->get(route('admin.logistics.purchase-requests.help'))->assertStatus(200);
    $this->actingAs($user)->get(route('admin.students.courses.help'))->assertStatus(200);
    $this->actingAs($user)->get(route('admin.project-docs.documents.help'))->assertStatus(200);
});