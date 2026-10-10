<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FeaturedCopypastaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property string $copypasta_id
 * @property int|null $picked_by_id
 */
#[Fillable(['date', 'copypasta_id', 'picked_by_id'])]
class FeaturedCopypasta extends Model
{
    /** @use HasFactory<FeaturedCopypastaFactory> */
    use HasFactory;

    protected $table = 'featured_copypastas';

    protected $primaryKey = 'date';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Copypasta, $this>
     */
    public function copypasta(): BelongsTo
    {
        return $this->belongsTo(Copypasta::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by_id')->withTrashed();
    }
}
