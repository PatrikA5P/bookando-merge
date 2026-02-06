<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('position');
            $table->string('department');
            $table->string('status')->default('ACTIVE');
            $table->string('role')->default('EMPLOYEE');
            $table->date('hire_date');
            $table->date('exit_date')->nullable();
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('street')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable()->default('CH');
            $table->integer('salary_minor')->default(0);
            $table->integer('vacation_days_total')->default(25);
            $table->integer('vacation_days_used')->default(0);
            $table->integer('employment_percent')->default(100);
            $table->string('social_security_number')->nullable();
            $table->json('assigned_service_ids')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
