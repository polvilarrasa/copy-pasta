<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A folder the staff made private stays private: while public_locked_at is set, its owner cannot make it public
     * again. The reason is the one the staff gave, shown to the owner. Unlocking clears both; it does not make the
     * folder public.
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->timestamp('public_locked_at')->nullable()->after('public_id');
            $table->string('public_lock_reason', 500)->nullable()->after('public_locked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn(['public_locked_at', 'public_lock_reason']);
        });
    }
};
