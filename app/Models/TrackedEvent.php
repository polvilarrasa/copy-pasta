<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use Database\Factories\TrackedEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of the partitioned `events` table. Rows are written after the action that caused them and are never edited;
 * the only change allowed is clearing `user_id` when the account is anonymized.
 */
#[Fillable(['type', 'user_id', 'visitor_hash', 'copypasta_id', 'context', 'created_at'])]
class TrackedEvent extends Model
{
    /** @use HasFactory<TrackedEventFactory> */
    use HasFactory;

    protected $table = 'events';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Copypasta, $this>
     */
    public function copypasta(): BelongsTo
    {
        return $this->belongsTo(Copypasta::class);
    }
}
