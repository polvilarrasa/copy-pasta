<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $copypasta_id
 * @property int $reporter_id
 * @property ReportReason $reason
 * @property string|null $details
 * @property ReportStatus $status
 * @property int|null $resolved_by_id
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_note
 */
#[Fillable(['copypasta_id', 'copypasta_revision_id', 'reporter_id', 'contact_email', 'reason', 'weight', 'details', 'status'])]
class Report extends Model
{
    /** Total weight of pending reports that hides a copy-pasta automatically. */
    public const AUTO_HIDE_THRESHOLD = 5;

    /** Weight of a report by a trusted member. A report by any other member, or an anonymous notice, weighs 1. */
    public const TRUSTED_WEIGHT = 3;

    public const DEFAULT_WEIGHT = 1;

    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Report>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ReportStatus::Pending);
    }

    /**
     * @return BelongsTo<Copypasta, $this>
     */
    public function copypasta(): BelongsTo
    {
        return $this->belongsTo(Copypasta::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id')->withTrashed();
    }
}
