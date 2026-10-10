<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The first time each member copied each copy-pasta. Inserting is what decides that a copy counts for affinity only once, and the table excludes already-copied copy-pastas from the "Para ti" feed without reading events.
     */
    public function up(): void
    {
        Schema::create('user_copied_copypastas', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->char('copypasta_id', 26);
            $table->timestamp('created_at');

            $table->foreign('copypasta_id')->references('id')->on('copypastas')->restrictOnDelete();
            $table->primary(['user_id', 'copypasta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_copied_copypastas');
    }
};
