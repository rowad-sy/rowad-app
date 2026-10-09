<?php

use App\Models\Admin\AdDesignRequest;
use App\Models\Admin\Center;
use App\Models\Admin\Permission;
use App\Models\User;

function adCentersAdmin(): User
{
    return User::factory()->create(['type' => 'super-admin', 'must_change_password' => false]);
}

test('ad design request can be assigned to multiple centers', function () {
    $admin = adCentersAdmin();
    $pm2 = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $c1 = Center::create(['name' => 'عفرين', 'code' => 'B01']);
    $c2 = Center::create(['name' => 'جرابلس', 'code' => 'B02']);
    $c3 = Center::create(['name' => 'اعزاز', 'code' => 'B03']);

    $this->actingAs($admin)->post('/admin/ad-design-requests', [
        'title' => 'إعلان توظيف',
        'refer_to_pm2_id' => $pm2->id,
        'center_ids' => [$c2->id, $c1->id],
    ])->assertRedirect();

    $ad = AdDesignRequest::firstWhere('title', 'إعلان توظيف');

    expect($ad->centers->pluck('id')->sort()->values()->all())->toBe([$c1->id, $c2->id])
        ->and((int) $ad->center_id)->toBe($ad->centers->first()->id); // الأول رئيسي

    // العرض يعرض كل المراكز
    $this->actingAs($admin)->get("/admin/ad-design-requests/{$ad->id}")
        ->assertOk()
        ->assertSee('عفرين')
        ->assertSee('جرابلس');

    // الفلتر بمركز يطابق أي مركز مرتبط
    $this->actingAs($admin)->get("/admin/ad-design-requests?center_id={$c1->id}")
        ->assertOk()
        ->assertSee('إعلان توظيف');

    // التعديل يبدّل المراكز (استبدال كامل)
    $this->actingAs($admin)->put("/admin/ad-design-requests/{$ad->id}", [
        'title' => 'إعلان توظيف',
        'refer_to_pm2_id' => $pm2->id,
        'center_ids' => [$c3->id],
    ])->assertRedirect();

    $ad->refresh()->load('centers');
    expect($ad->centers->pluck('id')->all())->toBe([$c3->id])
        ->and((int) $ad->center_id)->toBe($c3->id);
});

test('ad design request falls back to employee center when none selected', function () {
    $creator = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    $pm2 = User::factory()->create(['type' => 'employee', 'must_change_password' => false]);
    Permission::create([
        'user_id' => $creator->id,
        'model_names' => ['App\Models\Admin\AdDesignRequest'],
        'can_view' => true, 'can_create' => true,
    ]);
    $center = Center::create(['name' => 'مركز الموظف', 'code' => null]);
    \App\Models\Admin\Hr\Employee::create([
        'user_id' => $creator->id,
        'employee_code' => 'AD-CENTER-1',
        'first_name_ar' => '-test',
        'last_name_ar' => 'test',
        'gender' => 'male',
        'status' => 'active',
        'center_id' => $center->id,
    ]);

    $this->actingAs($creator)->post('/admin/ad-design-requests', [
        'title' => 'بلا مراكز',
        'refer_to_pm2_id' => $pm2->id,
    ])->assertRedirect();

    $ad = AdDesignRequest::firstWhere('title', 'بلا مراكز');
    expect((int) $ad->center_id)->toBe($center->id)
        ->and($ad->centers->pluck('id')->all())->toBe([$center->id]);
});
