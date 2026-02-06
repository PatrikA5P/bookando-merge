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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('SERVICE');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('price_minor');
            $table->integer('sale_price_minor')->nullable();
            $table->string('currency')->default('CHF');
            $table->integer('duration');
            $table->boolean('active')->default(true);
            $table->string('image')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
