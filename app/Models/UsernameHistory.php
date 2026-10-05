<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UsernameHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $username
 * @property Carbon $changed_at
 */
#[Fillable(['user_id', 'username', 'changed_at'])]
class UsernameHistory extends Model
{
    /** @use HasFactory<UsernameHistoryFactory> */
    use HasFactory;

    protected $table = 'username_history';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
