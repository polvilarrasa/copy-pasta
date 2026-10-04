<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModerationActionType;
use Database\Factories\ModerationActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $actor_id
 * @property ModerationActionType $action
 * @property string $subject_type
 * @property string $subject_id
 * @property string|null $reason
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'reason', 'meta'])]
class ModerationAction extends Model
{
    /** @use HasFactory<ModerationActionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ModerationActionType::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
