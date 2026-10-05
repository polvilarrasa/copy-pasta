<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CopypastaRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One published text of a copy-pasta. Revisions are never edited, so they only carry a creation time.
 */
#[Fillable(['copypasta_id', 'title', 'body'])]
class CopypastaRevision extends Model
{
    /** @use HasFactory<CopypastaRevisionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Copypasta, $this>
     */
    public function copypasta(): BelongsTo
    {
        return $this->belongsTo(Copypasta::class);
    }
}
