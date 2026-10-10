<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The tags a member chose as favorites. They are kept apart from the affinity scores: they add a fixed bonus on read and never decay.
     */
    public function up(): void
    {
        Schema::create('user_favorite_tags', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('tag_id')->constrained()->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['user_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_favorite_tags');
    }
};
