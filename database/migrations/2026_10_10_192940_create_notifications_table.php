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
     * `type` holds the NotificationType value, not a class name, and `data` is jsonb so the grouping lookup can match
     * on copypasta_id. That lookup is always narrowed by user, type, unread and a one-hour window first.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        // The bell counts unread notifications on every page: only the unread rows are indexed.
        DB::statement('CREATE INDEX notifications_unread_index ON notifications (notifiable_type, notifiable_id) WHERE read_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
