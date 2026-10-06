<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * الأدوار والمجموعات يشتركان في جدول groups ويميّزها عمود kind.
     * التسمية كانت تُقارن عالمياً فتتعارض «مجموعة» مع «دور» بنفس الاسم —
     * تصبح الوحدة الآن لكل نوع على حدة (index مركّب kind+name على مستوى القاعدة).
     */
    public function up(): void
    {
        $duplicates = DB::table('groups')
            ->selectRaw('kind, name, COUNT(*) as c')
            ->groupBy('kind', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            return;
        }

        Schema::table('groups', function (Blueprint $table) {
            $table->unique(['kind', 'name'], 'groups_kind_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique('groups_kind_name_unique');
        });
    }
};
