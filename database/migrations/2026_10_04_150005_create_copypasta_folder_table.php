<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('copypasta_folder', function (Blueprint $table) {
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('copypasta_id')->constrained('copypastas')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['folder_id', 'copypasta_id']);
            $table->index('copypasta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copypasta_folder');
    }
};
