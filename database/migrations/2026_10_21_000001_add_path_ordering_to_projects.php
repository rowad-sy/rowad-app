<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_paths', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('code');
            $table->index(['sort_order', 'name'], 'project_paths_order_name_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index(['path_id', 'sort_order', 'name'], 'projects_path_order_name_index');
        });

        // Preserve the existing tree's path order (creation/id order) as the
        // initial editable path ranking.
        foreach (DB::table('project_paths')->orderBy('id')->pluck('id') as $index => $pathId) {
            DB::table('project_paths')->where('id', $pathId)->update(['sort_order' => $index + 1]);
        }

        // The old project number was global. Keep its relative order within
        // each path, but restart numbering at 1 for every path (and orphans).
        $nextByPath = [];
        $projects = DB::table('projects')
            ->orderBy('path_id')
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'path_id']);

        foreach ($projects as $project) {
            $group = $project->path_id === null ? 'orphan' : 'path:'.$project->path_id;
            $nextByPath[$group] = ($nextByPath[$group] ?? 0) + 1;

            DB::table('projects')->where('id', $project->id)->update([
                'sort_order' => $nextByPath[$group],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_path_order_name_index');
        });

        Schema::table('project_paths', function (Blueprint $table) {
            $table->dropIndex('project_paths_order_name_index');
            $table->dropColumn('sort_order');
        });
    }
};
