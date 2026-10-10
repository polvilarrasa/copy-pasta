<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Whether an upvote counted towards its author's achievements when it was cast (a verified voter whose account was
     * at least 72 hours old). Withdrawing the vote only takes progress back when it had been added. Existing votes
     * are classified by app:backfill-achievements.
     */
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->boolean('counts_for_achievements')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropColumn('counts_for_achievements');
        });
    }
};
