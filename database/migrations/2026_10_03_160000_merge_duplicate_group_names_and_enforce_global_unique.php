<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * الأسماء فريدة على مستوى النظام كله (أدوار + مجموعات معاً) حتى لا يوجد
 * سجلّان متعارضان لا يراهما المستخدم في شاشته. دمج أي تكرار موجود:
 * السجل الأغنى (أعضاء/صلاحيات) يبقى ويُحوَّل إلى دور لتظهر في شاشة الأدوار،
 * والفارغ يُحذف بعد نقل memberships وصلاحياته إليه.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dups = DB::table('groups')
            ->selectRaw('name, COUNT(*) as c')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($dups as $name) {
            $rows = DB::table('groups')->where('name', $name)->orderBy('id')->get();

            $survivor = $rows->first(function ($g) {
                $members = DB::table('group_user')->where('group_id', $g->id)->count();
                $perms = DB::table('permissions')->where('group_id', $g->id)->count();

                return $members > 0 || $perms > 0;
            }) ?? $rows->first();

            foreach ($rows as $row) {
                if ($row->id === $survivor->id) {
                    continue;
                }

                foreach (DB::table('group_user')->where('group_id', $row->id)->get() as $member) {
                    $exists = DB::table('group_user')
                        ->where('group_id', $survivor->id)
                        ->where('user_id', $member->user_id)
                        ->exists();
                    if (! $exists) {
                        DB::table('group_user')->insert([
                            'group_id' => $survivor->id,
                            'user_id' => $member->user_id,
                            'center_id' => $member->center_id,
                            'project_id' => $member->project_id,
                            'cohort_id' => $member->cohort_id,
                            'created_at' => $member->created_at,
                            'updated_at' => $member->updated_at,
                        ]);
                    }
                }

                DB::table('permissions')->where('group_id', $row->id)->update(['group_id' => $survivor->id]);
                DB::table('group_user')->where('group_id', $row->id)->delete();
                DB::table('groups')->where('id', $row->id)->delete();
            }

            DB::table('groups')->where('id', $survivor->id)->update(['kind' => 'role']);
        }

        if (Schema::hasTable('groups')) {
            try {
                Schema::table('groups', function (Blueprint $table) {
                    $table->dropUnique('groups_kind_name_unique');
                });
            } catch (\Throwable) {
                // القيد المركّب غير موجود (بيئة تخطّته سابقاً)
            }

            $remaining = DB::table('groups')
                ->selectRaw('name, COUNT(*) as c')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (! $remaining) {
                Schema::table('groups', function (Blueprint $table) {
                    $table->unique('name', 'groups_name_unique');
                });
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropUnique('groups_name_unique');
            });
        } catch (\Throwable) {
        }
    }
};
