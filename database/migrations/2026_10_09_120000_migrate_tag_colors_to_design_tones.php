<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The design system has 5 tag tones (t1 mint, t2 orange, t3 violet, t4 yellow, t5 pink); the Filament picker
     * used to offer 10 colors. Same mapping the view layer used before this migration, via TagColor::tone().
     *
     * @var array<string, string>
     */
    private const MAP = [
        'green' => 't1',
        'teal' => 't1',
        'orange' => 't2',
        'amber' => 't2',
        'purple' => 't3',
        'indigo' => 't3',
        'blue' => 't3',
        'red' => 't4',
        'gray' => 't4',
        'pink' => 't5',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $tone) {
            DB::table('tags')->where('color', $old)->update(['color' => $tone]);
        }
    }

    /**
     * Irreversible: several old colors collapse onto the same tone, so which one a tag had is not kept anywhere.
     */
    public function down(): void
    {
        //
    }
};
