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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('online');
            $table->string('difficulty')->default('beginner');
            $table->string('visibility')->default('public');
            $table->integer('duration_hours')->nullable();
            $table->integer('price_minor')->default(0);
            $table->string('currency')->default('CHF');
            $table->string('image')->nullable();
            $table->boolean('certificate')->default(false);
            $table->integer('max_participants')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
