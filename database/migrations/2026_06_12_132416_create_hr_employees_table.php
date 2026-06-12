<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 20)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('id_number', 50)->nullable();
            $table->string('first_name_ar', 100);
            $table->string('last_name_ar', 100);
            $table->string('first_name_en', 100)->nullable();
            $table->string('last_name_en', 100)->nullable();
            $table->string('father_name_ar', 100)->nullable();
            $table->string('father_name_en', 100)->nullable();
            $table->string('mother_name_ar', 100)->nullable();
            $table->string('mother_name_en', 100)->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->integer('children_count')->default(0);
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('has_photo')->default(false);
            $table->boolean('has_cv')->default(false);
            $table->boolean('has_id_copy')->default(false);
            $table->boolean('has_qualification')->default(false);
            $table->boolean('has_experience_certs')->default(false);
            $table->boolean('has_offer_letter')->default(false);
            $table->boolean('has_contract_doc')->default(false);
            $table->boolean('has_employee_data')->default(false);
            $table->boolean('has_job_description')->default(false);
            $table->boolean('has_signature_movements')->default(false);
            $table->boolean('has_security_audit')->default(false);
            $table->boolean('has_reference_audit')->default(false);
            $table->boolean('has_code_of_conduct')->default(false);
            $table->boolean('has_clearance')->default(false);
            $table->boolean('has_receipt')->default(false);
            $table->boolean('has_resignation')->default(false);
            $table->boolean('has_verbal_warning_doc')->default(false);
            $table->boolean('has_written_warning_doc')->default(false);
            $table->boolean('has_termination_warning_doc')->default(false);
            $table->boolean('has_termination_doc')->default(false);
            $table->boolean('has_blacklist_doc')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
