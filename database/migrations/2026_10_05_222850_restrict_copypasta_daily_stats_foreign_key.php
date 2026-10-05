<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily statistics are facts about a copy-pasta, so a physical delete must fail instead of silently removing them.
     */
    public function up(): void
    {
        Schema::table('copypasta_daily_stats', function (Blueprint $table) {
            $table->dropForeign(['copypasta_id']);
            $table->foreign('copypasta_id')->references('id')->on('copypastas')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copypasta_daily_stats', function (Blueprint $table) {
            $table->dropForeign(['copypasta_id']);
            $table->foreign('copypasta_id')->references('id')->on('copypastas')->cascadeOnDelete();
        });
    }
};
