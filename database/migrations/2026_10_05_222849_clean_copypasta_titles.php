<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Removes the invisible direction and zero-width characters from existing titles, as publishing and editing do now,
     * and recomputes the slug from the cleaned title. A title that is only invisible characters is left as it is.
     * Revisions keep the text as it was published.
     */
    public function up(): void
    {
        DB::table('copypastas')->orderBy('id')->chunkById(500, function ($copypastas): void {
            foreach ($copypastas as $copypasta) {
                $title = trim(preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}-\x{200D}\x{FEFF}]/u', '', $copypasta->title) ?? '');

                if ($title === '' || $title === $copypasta->title) {
                    continue;
                }

                DB::table('copypastas')->where('id', $copypasta->id)->update([
                    'title' => $title,
                    'slug' => Str::slug($title) ?: $copypasta->id,
                ]);
            }
        });
    }

    /**
     * Irreversible: the removed characters are not kept anywhere.
     */
    public function down(): void
    {
        //
    }
};
