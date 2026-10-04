<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $copypasta_id
 * @property int $value
 */
#[Fillable(['user_id', 'copypasta_id', 'value'])]
class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
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
