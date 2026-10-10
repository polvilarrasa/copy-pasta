<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Attributed visits brought by a member's shared links, per day. The private stats page reads it, so it never
     * has to count over the raw events.
     */
    public function up(): void
    {
        Schema::create('user_daily_referrals', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedInteger('visits')->default(0);

            $table->primary(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_daily_referrals');
    }
};
