<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every folder starts private. The public_id is a ULID given the first time the folder is made public and kept
     * afterwards, so the same address comes back when the owner makes it public again.
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('description');
            $table->char('public_id', 26)->nullable()->unique()->after('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'public_id']);
        });
    }
};
