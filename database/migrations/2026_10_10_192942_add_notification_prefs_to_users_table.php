<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Null means "never touched": every notification type is on by default.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('notification_prefs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_prefs');
        });
    }
};
