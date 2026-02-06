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
        Schema::create('salary_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->integer('gross_minor');
            $table->integer('ahv_minor')->default(0);
            $table->integer('alv_minor')->default(0);
            $table->integer('bvg_minor')->default(0);
            $table->integer('nbu_minor')->default(0);
            $table->integer('tax_minor')->default(0);
            $table->integer('net_minor');
            $table->string('status')->default('DRAFT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_declarations');
    }
};
