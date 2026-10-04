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
        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('subject_type', 40);
            $table->string('subject_id', 40);
            $table->text('reason')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index('actor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');
    }
};
