<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The oldest admin becomes the owner, the only one who can promote, demote, ban or delete other admins.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('role');
        });

        $ownerId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

        if ($ownerId !== null) {
            DB::table('users')->where('id', $ownerId)->update(['is_owner' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_owner');
        });
    }
};
