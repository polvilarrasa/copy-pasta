<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Adds the username change date and the share code. Existing members get a random share code each, drawn
     * without relation to their id or username, and the column is unique afterwards.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('username_changed_at')->nullable()->after('username');
            $table->string('share_code', 8)->nullable()->after('username_changed_at');
        });

        DB::table('users')->orderBy('id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update(['share_code' => $this->uniqueShareCode()]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('share_code', 8)->nullable(false)->change();
            $table->unique('share_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['share_code']);
            $table->dropColumn(['username_changed_at', 'share_code']);
        });
    }

    private function uniqueShareCode(): string
    {
        do {
            $code = Str::random(8);
        } while (DB::table('users')->where('share_code', $code)->exists());

        return $code;
    }
};
