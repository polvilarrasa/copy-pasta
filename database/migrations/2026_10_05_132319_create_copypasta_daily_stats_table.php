<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per copy-pasta and day. The event columns are rebuilt from `events` by an hourly job; impressions come from the
     * feed as a plain counter and are never derived from events. Favorites are net, so they can drop below zero in a day.
     */
    public function up(): void
    {
        Schema::create('copypasta_daily_stats', function (Blueprint $table) {
            $table->foreignUlid('copypasta_id')->constrained('copypastas')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('copies')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('upvotes')->default(0);
            $table->unsignedInteger('downvotes')->default(0);
            $table->integer('favorites')->default(0);
            $table->unsignedInteger('impressions')->default(0);

            $table->primary(['copypasta_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copypasta_daily_stats');
    }
};
