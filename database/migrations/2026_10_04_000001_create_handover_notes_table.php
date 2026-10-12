<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * محضر تسليم عهدة (GRN) لمعدات طلب شراء مُنجَز — نموذج RCD-OPR-PS-F04:
 * رقم المحضر + بيانات الطلب/المركز/المشروع، بنود برقم تسلسلي/وحدة/كمية/حالة،
 * وتسليم من (المستخدم المخول) واستلام إلى (مستخدم مسجل أو اسم يدوي).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handover_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('logistics_purchase_requests')->cascadeOnDelete();
            $table->string('grn_number', 60);
            $table->date('handover_date');
            $table->foreignId('delivered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('delivered_by_name');
            $table->string('delivered_by_position')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('received_by_name');
            $table->string('received_by_position')->nullable();
            $table->text('note')->nullable();
            $table->json('items')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('grn_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_notes');
    }
};
