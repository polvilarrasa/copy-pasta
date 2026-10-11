<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `is_template` marks a copy-pasta whose {{variables}} are filled in when copying. `parent_id` points to the
     * copy-pasta a variant was made from (the direct parent only); it is the one foreign key of this table that nulls
     * on delete, so a variant outlives a parent that disappears physically. `template_copies_by_others` counts the
     * copies made by anyone but the author while the copy-pasta was a template, the number the Plantillero
     * achievement reads.
     */
    public function up(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('is_nsfw');
            $table->foreignUlid('parent_id')->nullable()->after('user_id')->constrained('copypastas')->nullOnDelete();
            $table->unsignedInteger('template_copies_by_others')->default(0)->after('copies_count');

            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copypastas', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['is_template', 'template_copies_by_others']);
        });
    }
};
