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
     * The random feed walks an indexed key instead of hashing every row on each request. The default is evaluated
     * per row, so existing copy-pastas get a key too and new ones get it on insert without application code.
     */
    public function up(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->integer('random_key')->default(DB::raw('floor(random() * 2147483647)::int'));
            $table->index(['random_key', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->dropIndex(['random_key', 'id']);
            $table->dropColumn('random_key');
        });
    }
};
