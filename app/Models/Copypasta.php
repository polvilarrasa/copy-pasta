<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeedSort;
use App\Enums\ReportStatus;
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
            'my_vote' => 'integer',
            'is_favorite' => 'boolean',
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
     * Every published text, oldest first. The last one is the current text.
     *
     * @return HasMany<CopypastaRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CopypastaRevision::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_id')->withTrashed();
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

    /**
     * @return HasMany<Report, $this>
     */
    public function pendingReports(): HasMany
    {
        return $this->hasMany(Report::class)->where('status', ReportStatus::Pending);
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
     * Adds the viewer's own vote and favorite flag as columns, so cards need no extra queries.
     *
     * @param  Builder<Copypasta>  $query
     */
    public function scopeWithViewerState(Builder $query, ?User $viewer): void
    {
        $query->addSelect('copypastas.*');

        if ($viewer === null) {
            $query->selectRaw('NULL AS my_vote, 0 AS is_favorite');

            return;
        }

        $query->addSelect([
            'my_vote' => Vote::query()
                ->select('value')
                ->whereColumn('votes.copypasta_id', 'copypastas.id')
                ->where('votes.user_id', $viewer->getKey())
                ->limit(1),
        ])->selectRaw(
            '(EXISTS (SELECT 1 FROM copypasta_folder AS cf INNER JOIN folders AS f ON f.id = cf.folder_id'
            .' WHERE cf.copypasta_id = copypastas.id AND f.user_id = ? AND f.is_default = TRUE))::int AS is_favorite',
            [$viewer->getKey()],
        );
    }

    /**
     * Drops copy-pastas whose tags are all deactivated; untagged ones stay visible.
     *
     * @param  Builder<Copypasta>  $query
     */
    public function scopeNotOnlyInactiveTags(Builder $query): void
    {
        $query->where(fn (Builder $tagged) => $tagged
            ->doesntHave('tags')
            ->orWhereHas('tags', fn (Builder $tag) => $tag->where('tags.is_active', true)));
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
     * The random feed is a walk over `random_key` starting at a seed; see {@see scopeRandomKeyFrom()}.
     *
     * @param  Builder<Copypasta>  $query
     */
    public function scopeSort(Builder $query, FeedSort $sort): void
    {
        match ($sort) {
            FeedSort::Random => $query->orderBy('random_key')->orderBy('id'),
            FeedSort::TopWeek => $query->where('published_at', '>=', now()->subDays(7))
                ->orderByDesc('score')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            FeedSort::TopMonth => $query->where('published_at', '>=', now()->subDays(30))
                ->orderByDesc('score')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            FeedSort::TopAll => $query->orderByDesc('score')->orderByDesc('published_at')->orderByDesc('id'),
            FeedSort::Newest => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /**
     * @param  Builder<Copypasta>  $query
     */
    public function scopeRandomKeyFrom(Builder $query, int $seed): void
    {
        $query->where('random_key', '>=', $seed);
    }

    /**
     * @param  Builder<Copypasta>  $query
     */
    public function scopeRandomKeyBefore(Builder $query, int $seed): void
    {
        $query->where('random_key', '<', $seed);
    }
}
