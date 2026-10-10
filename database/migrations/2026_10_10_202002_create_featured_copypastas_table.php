<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The copy-pasta of the day. The unique copypasta_id is what keeps one from being featured twice; a replaced row is deleted, which makes its copy-pasta eligible again. picked_by_id is null when the daily command chose it.
     */
    public function up(): void
    {
        Schema::create('featured_copypastas', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->char('copypasta_id', 26)->unique();
            $table->foreignId('picked_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->foreign('copypasta_id')->references('id')->on('copypastas')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('featured_copypastas');
    }
};
