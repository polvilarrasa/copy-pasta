<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Jobs\SyncCopypastaOgImageJob;
use App\Mail\CopypastaHiddenMail;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use App\Notifications\CopypastaHiddenNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ConcealCopypasta
{
    public function __construct(
        private AcceptCopypastaReports $acceptReports,
        private AdjustPublishedProgress $adjustPublishedProgress,
    ) {}

    /**
     * Hides the copy-pasta and logs who did it. Automatic hides pass $acceptsPendingReports = false so the
     * reports stay pending and keep the copy-pasta at the top of the moderation queue for review.
     * The author is emailed and notified in the app after the transaction commits; the reporters whose reports were
     * accepted are notified too.
     */
    public function handle(?User $actor, Copypasta $copypasta, string $reason, bool $acceptsPendingReports): Copypasta
    {
        $copypasta = DB::transaction(function () use ($actor, $copypasta, $reason, $acceptsPendingReports): Copypasta {
            $countedBefore = AdjustPublishedProgress::counts($copypasta);

            $copypasta->forceFill([
                'hidden_at' => now(),
                'hidden_by_id' => $actor?->getKey(),
                'hidden_reason' => $reason,
            ])->save();

            $this->adjustPublishedProgress->handle($copypasta, $countedBefore);

            if ($acceptsPendingReports) {
                $this->acceptReports->handle($actor, $copypasta);
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

        GetFeaturedCopypasta::forgetIfFeatured($copypasta);

        SyncCopypastaOgImageJob::dispatch($copypasta->getKey());

        // Reload: callers may hold a copy loaded with a partial user select that has no email.
        $copypasta->load('user');

        Mail::to($copypasta->user)->queue(new CopypastaHiddenMail($copypasta));
        $copypasta->user->notify(new CopypastaHiddenNotification($copypasta));

        return $copypasta;
    }
}
