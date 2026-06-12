<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->json('model_names')->after('group_id')->nullable();
        });

        DB::table('permissions')->orderBy('id')->each(function ($perm) {
            DB::table('permissions')->where('id', $perm->id)->update([
                'model_names' => json_encode([$perm->model_name]),
            ]);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('model_name');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('model_name')->after('group_id');
        });

        DB::table('permissions')->orderBy('id')->each(function ($perm) {
            $names = json_decode($perm->model_names ?? '[]', true);
            DB::table('permissions')->where('id', $perm->id)->update([
                'model_name' => $names[0] ?? '',
            ]);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('model_names');
        });
    }
};
