<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The title the member chose to show next to their name. It is read straight from the user row, so showing it
     * costs no extra query.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('title_key', 40)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('title_key');
        });
    }
};
