<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per member and achievement. The unique index is what makes granting idempotent, also under concurrent
     * evaluations, and what stops a revoked achievement from being granted again: the revoked row is still there.
     */
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('achievement_key', 40);
            $table->timestamp('unlocked_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('revoke_reason')->nullable();

            $table->unique(['user_id', 'achievement_key']);
            $table->index('achievement_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
