<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The path of the Open Graph image on the configured disk. The file name carries a hash of the template version
     * and of the text, so an edit gets a new name. Null while there is none, and the generic image is used instead.
     */
    public function up(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->string('og_image_path', 120)->nullable()->after('body_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->dropColumn('og_image_path');
        });
    }
};
