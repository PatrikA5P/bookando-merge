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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('role')->default('employee')->after('last_name');
            $table->string('avatar')->nullable()->after('role');

            // Drop old unique index on email, add composite unique
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->string('email')->unique()->change();

            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'first_name', 'last_name', 'role', 'avatar']);
        });
    }
};
