<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeedSort;
use Database\Factories\CopypastaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string $body_hash
 * @property bool $is_nsfw
 * @property int $upvotes_count
 * @property int $downvotes_count
 * @property int $score
 * @property int $favorites_count
 * @property int $copies_count
 * @property Carbon|null $published_at
 * @property Carbon|null $edited_at
 * @property Carbon|null $hidden_at
 * @property int|null $hidden_by_id
 * @property string|null $hidden_reason
 * @property Carbon|null $deleted_at
 */
#[Fillable(['user_id', 'title', 'slug', 'body', 'is_nsfw', 'published_at'])]
class Copypasta extends Model
{
    /** @use HasFactory<CopypastaFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_nsfw' => 'boolean',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
            'score' => 'integer',
            'favorites_count' => 'integer',
            'copies_count' => 'integer',
            'published_at' => 'datetime',
            'edited_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Copypasta $copypasta): void {
            if (blank($copypasta->slug)) {
                $copypasta->slug = Str::slug($copypasta->title);
            }
        });
    }

    /**
     * @return Attribute<string, string>
     */
    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): array => [
                'body' => $value,
                'body_hash' => self::hashBody($value),
            ],
        );
    }

    public static function normalizeBody(string $body): string
    {
        return Str::of($body)->squish()->lower()->value();
    }

    public static function hashBody(string $body): string
    {
        return hash('sha256', self::normalizeBody($body));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_id');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<Folder, $this>
     */
    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class)->withPivot('created_at');
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * @param  Builder<Copypasta>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNotNull('published_at')->whereNull('hidden_at');
    }

    /**
     * @param  Builder<Copypasta>  $query
     */
    public function scopeNsfw(Builder $query, bool $include): void
    {
        if (! $include) {
            $query->where('is_nsfw', false);
        }
    }

    /**
     * @param  Builder<Copypasta>  $query
     * @param  array<int, string>  $tagSlugs
     */
    public function scopeWithAllTags(Builder $query, array $tagSlugs): void
    {
        foreach ($tagSlugs as $tagSlug) {
            $query->whereHas('tags', fn (Builder $tagQuery) => $tagQuery->where('tags.slug', $tagSlug));
        }
    }

    /**
     * @param  Builder<Copypasta>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->whereRaw(
            "search_vector @@ plainto_tsquery('simple', immutable_unaccent(?))",
            [$term],
        );
    }

    /**
     * @param  Builder<Copypasta>  $query
     */
    public function scopeSort(Builder $query, FeedSort $sort, ?string $randomSeed = null): void
    {
        match ($sort) {
            FeedSort::Random => $query->orderByRaw('md5(id || ?)', [$randomSeed ?? Str::random(16)]),
            FeedSort::TopWeek => $query->where('published_at', '>=', now()->subDays(7))
                ->orderByDesc('score')
                ->orderByDesc('published_at'),
            FeedSort::TopMonth => $query->where('published_at', '>=', now()->subDays(30))
                ->orderByDesc('score')
                ->orderByDesc('published_at'),
            FeedSort::TopAll => $query->orderByDesc('score')->orderByDesc('published_at'),
            FeedSort::Newest => $query->orderByDesc('published_at'),
        };
    }
}
