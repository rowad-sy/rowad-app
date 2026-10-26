<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('role_key', 80)->nullable()->after('kind');
            $table->unique('role_key', 'groups_role_key_unique');
        });

        DB::table('groups')->where('kind', 'role')->where('name', 'مدير مشروع')->update(['role_key' => 'project_manager']);
        DB::table('groups')->where('kind', 'role')->where('name', 'مدير المشاريع')->update(['role_key' => 'projects_manager']);
        DB::table('groups')->where('kind', 'role')->where('name', 'مسؤول الحركة')->update(['role_key' => 'movement_officer']);
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique('groups_role_key_unique');
            $table->dropColumn('role_key');
        });
    }
};
