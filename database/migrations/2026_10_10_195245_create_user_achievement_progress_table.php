<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The running value of every achievement metric per member. Evaluating achievements and drawing the progress bars
     * read this table; neither counts over events or votes.
     */
    public function up(): void
    {
        Schema::create('user_achievement_progress', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('metric', 32);
            $table->unsignedBigInteger('value')->default(0);

            $table->primary(['user_id', 'metric']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievement_progress');
    }
};
