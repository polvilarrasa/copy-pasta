<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A milestone is reached once in the life of a copy-pasta, even if its counter drops and climbs again. The unique
     * index is what makes the detection idempotent, including under concurrent requests.
     */
    public function up(): void
    {
        Schema::create('copypasta_milestones', function (Blueprint $table) {
            $table->id();
            $table->char('copypasta_id', 26);
            $table->string('metric', 16);
            $table->unsignedInteger('threshold');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('copypasta_id')->references('id')->on('copypastas')->restrictOnDelete();
            $table->unique(['copypasta_id', 'metric', 'threshold']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copypasta_milestones');
    }
};
