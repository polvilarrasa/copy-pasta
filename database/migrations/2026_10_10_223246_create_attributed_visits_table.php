<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per visitor, link owner and day: the unique key is what makes an attributed visit count once. The
     * visitor key is "u:{id}" for a member and the daily visitor hash for anyone else. Rows older than two days are
     * deleted by `visits:prune`, because they can no longer collide with a new visit.
     */
    public function up(): void
    {
        Schema::create('attributed_visits', function (Blueprint $table) {
            $table->string('visitor_key', 64);
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->date('visited_on');

            $table->primary(['visitor_key', 'owner_id', 'visited_on']);
            $table->index('visited_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributed_visits');
    }
};
