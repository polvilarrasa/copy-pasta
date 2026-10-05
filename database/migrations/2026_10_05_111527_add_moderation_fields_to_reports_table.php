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
     * A report keeps the version it saw and its weight, which is fixed when the report is made. Notices from
     * anonymous visitors have no reporter but keep a contact address, and their reporter column is nullable.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->default(1)->after('reason');
            $table->foreignId('copypasta_revision_id')->nullable()->after('copypasta_id')
                ->constrained('copypasta_revisions')->nullOnDelete();
            $table->string('contact_email')->nullable()->after('reporter_id');
            $table->foreignId('reporter_id')->nullable()->change();
        });

        DB::statement(
            'UPDATE reports SET copypasta_revision_id = (SELECT copypasta_revisions.id FROM copypasta_revisions '
            .'WHERE copypasta_revisions.copypasta_id = reports.copypasta_id ORDER BY copypasta_revisions.id DESC LIMIT 1)',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('copypasta_revision_id');
            $table->dropColumn(['weight', 'contact_email']);
        });
    }
};
