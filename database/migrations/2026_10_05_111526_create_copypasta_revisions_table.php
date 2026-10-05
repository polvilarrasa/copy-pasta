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
     * Every published version of a copy-pasta is kept, so a report can show the text the reporter saw. Existing
     * copy-pastas get their current text as first version.
     */
    public function up(): void
    {
        Schema::create('copypasta_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('copypasta_id')->constrained('copypastas')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['copypasta_id', 'id']);
        });

        DB::table('copypastas')->orderBy('id')->chunkById(500, function ($copypastas): void {
            DB::table('copypasta_revisions')->insert($copypastas->map(fn ($copypasta): array => [
                'copypasta_id' => $copypasta->id,
                'title' => $copypasta->title,
                'body' => $copypasta->body,
                'created_at' => $copypasta->created_at ?? now(),
            ])->all());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copypasta_revisions');
    }
};
