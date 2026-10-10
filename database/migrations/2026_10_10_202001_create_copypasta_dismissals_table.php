<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Copy-pastas a member marked as "no me interesa".
     */
    public function up(): void
    {
        Schema::create('copypasta_dismissals', function (Blueprint $table) {
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
        Schema::dropIfExists('copypasta_dismissals');
    }
};
