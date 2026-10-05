<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Previous usernames with the date they were left. They only redirect old profile links for 90 days and never
     * reserve a name: the users table alone decides who holds a username.
     */
    public function up(): void
    {
        Schema::create('username_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('username', 30);
            $table->timestamp('changed_at');

            $table->index(['username', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('username_history');
    }
};
