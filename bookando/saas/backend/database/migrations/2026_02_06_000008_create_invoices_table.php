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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('DRAFT');
            $table->date('issue_date');
            $table->date('due_date');
            $table->integer('total_minor');
            $table->integer('tax_minor')->default(0);
            $table->string('currency')->default('CHF');
            $table->integer('dunning_level')->default(0);
            $table->string('qr_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
