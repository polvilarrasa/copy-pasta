<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $is_default
 * @property int $position
 */
#[Fillable(['user_id', 'name', 'is_default', 'position'])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'position' => 'integer',
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
     * @return BelongsToMany<Copypasta, $this>
     */
    public function copypastas(): BelongsToMany
    {
        return $this->belongsToMany(Copypasta::class)->withPivot('created_at');
    }
}
