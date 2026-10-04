<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('copypastas', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('slug');
            $table->text('body');
            $table->string('body_hash', 64);
            $table->boolean('is_nsfw')->default(false);
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->unsignedInteger('downvotes_count')->default(0);
            $table->integer('score')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('copies_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->foreignId('hidden_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('hidden_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('published_at');
            $table->index(['score', 'published_at']);
            $table->index('body_hash');
            $table->index('user_id');
        });

        DB::statement('create extension if not exists unaccent');

        DB::statement(<<<'SQL'
            create or replace function immutable_unaccent(text)
            returns text as $$
                select public.unaccent('public.unaccent', $1)
            $$ language sql immutable parallel safe strict
        SQL);

        DB::statement(<<<'SQL'
            alter table copypastas add column search_vector tsvector
            generated always as (
                setweight(to_tsvector('simple', immutable_unaccent(coalesce(title, ''))), 'A') ||
                setweight(to_tsvector('simple', immutable_unaccent(coalesce(body, ''))), 'B')
            ) stored
        SQL);

        DB::statement('create index copypastas_search_vector_index on copypastas using gin (search_vector)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copypastas');

        DB::statement('drop function if exists immutable_unaccent(text)');
    }
};
