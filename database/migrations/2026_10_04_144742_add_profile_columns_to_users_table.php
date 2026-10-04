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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->after('id');
            $table->string('role', 20)->default('user')->after('email');
            $table->boolean('show_nsfw')->default(false)->after('role');
            $table->timestamp('banned_at')->nullable()->after('show_nsfw');
            $table->string('ban_reason')->nullable()->after('banned_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['role']);
            $table->dropColumn(['username', 'role', 'show_nsfw', 'banned_at', 'ban_reason']);
        });
    }
};
