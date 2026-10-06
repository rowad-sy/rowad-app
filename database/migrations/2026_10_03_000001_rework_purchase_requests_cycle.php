<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
        });

        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->date('pr_date')->nullable()->after('request_number');
            $table->date('required_date')->nullable()->after('pr_date');
            $table->string('management_unit')->nullable()->after('required_date');
            $table->foreignId('refer_to_approver1_id')->nullable()->constrained('users')->nullOnDelete()->after('management_unit');
            $table->foreignId('refer_to_approver2_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_approver1_id');
            $table->foreignId('refer_to_approver3_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_approver2_id');
        });

        Schema::create('purchase_request_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('logistics_purchase_requests')->cascadeOnDelete();
            $table->string('role'); // requested_by | approver1 | approver2 | approver3
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
            $table->unique(['purchase_request_id', 'role']);
        });

        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->string('currency', 4)->default('USD')->after('unit');
            $table->timestamp('executed_at')->nullable()->after('budget_line');
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete()->after('executed_at');
        });

        // ترحيل البيانات القديمة إلى الدورة الجديدة
        DB::table('logistics_purchase_requests')->whereNull('pr_date')->update([
            'pr_date' => DB::raw('DATE(created_at)'),
        ]);

        DB::table('logistics_purchase_requests')->whereIn('status', ['pending', 'priced'])->update(['status' => 'review']);
        DB::table('logistics_purchase_requests')->where('status', 'pm_approved')->update(['status' => 'approved1']);
        DB::table('logistics_purchase_requests')->where('status', 'pm2_approved')->update(['status' => 'approved2']);
        // finance_approved و approved قديماً ⇒ بانتظار تنفيذ اللوجستي (approved)

        DB::table('logistics_purchase_requests')
            ->whereNotNull('refer_to_pm2_id')
            ->update(['refer_to_approver1_id' => DB::raw('refer_to_pm2_id')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_signatures');

        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('executed_by');
            $table->dropColumn(['currency', 'executed_at']);
        });

        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refer_to_approver3_id');
            $table->dropConstrainedForeignId('refer_to_approver2_id');
            $table->dropConstrainedForeignId('refer_to_approver1_id');
            $table->dropColumn(['pr_date', 'required_date', 'management_unit']);
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
