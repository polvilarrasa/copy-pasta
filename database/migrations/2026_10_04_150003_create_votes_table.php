<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('copypasta_id')->constrained('copypastas')->cascadeOnDelete();
            $table->smallInteger('value');
            $table->timestamps();

            $table->unique(['user_id', 'copypasta_id']);
        });

        DB::statement('alter table votes add constraint votes_value_check check (value in (-1, 1))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
