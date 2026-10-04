<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\ReportStatus;
use App\Mail\CopypastaHiddenMail;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ConcealCopypasta
{
    /**
     * Hides the copy-pasta and logs who did it. Automatic hides pass $acceptsPendingReports = false so the
     * reports stay pending and keep the copy-pasta at the top of the moderation queue for review.
     * The author is emailed after the transaction commits.
     */
    public function handle(?User $actor, Copypasta $copypasta, string $reason, bool $acceptsPendingReports): Copypasta
    {
        $copypasta = DB::transaction(function () use ($actor, $copypasta, $reason, $acceptsPendingReports): Copypasta {
            $copypasta->forceFill([
                'hidden_at' => now(),
                'hidden_by_id' => $actor?->getKey(),
                'hidden_reason' => $reason,
            ])->save();

            if ($acceptsPendingReports) {
                Report::query()
                    ->where('copypasta_id', $copypasta->getKey())
                    ->pending()
                    ->update([
                        'status' => ReportStatus::Accepted->value,
                        'resolved_by_id' => $actor?->getKey(),
                        'resolved_at' => now(),
                    ]);
            }

            ModerationAction::query()->create([
                'actor_id' => $actor?->getKey(),
                'action' => ModerationActionType::Hide,
                'subject_type' => $copypasta::class,
                'subject_id' => $copypasta->getKey(),
                'reason' => $reason,
            ]);

            return $copypasta;
        });

        // Reload: callers may hold a copy loaded with a partial user select that has no email.
        $copypasta->load('user');

        Mail::to($copypasta->user)->queue(new CopypastaHiddenMail($copypasta));

        return $copypasta;
    }
}
