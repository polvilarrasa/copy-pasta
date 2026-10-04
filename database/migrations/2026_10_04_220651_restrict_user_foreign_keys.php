<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Copy-pastas, votes and reports must go through the account anonymization, so a physical delete of a user
     * fails instead of silently removing other members' data and leaving the counters wrong.
     */
    public function up(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->foreign('reporter_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->foreign('reporter_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
