<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Lab404\Impersonate\Services\ImpersonateManager;

class StopImpersonating
{
    /**
     * Ends the impersonation, restores the admin's session and logs the end against the impersonated member.
     */
    public function handle(ImpersonateManager $impersonation): bool
    {
        throw_unless($impersonation->isImpersonating(), new AuthorizationException(__('moderation.impersonation.not_impersonating')));

        $impersonated = auth()->user();
        $impersonatorId = $impersonation->getImpersonatorId();

        throw_unless($impersonated instanceof User, AuthenticationException::class);

        return DB::transaction(function () use ($impersonation, $impersonated, $impersonatorId): bool {
            if (! $impersonation->leave()) {
                return false;
            }

            ModerationAction::query()->create([
                'actor_id' => $impersonatorId,
                'action' => ModerationActionType::ImpersonateEnd,
                'subject_type' => $impersonated::class,
                'subject_id' => $impersonated->getKey(),
            ]);

            return true;
        });
    }
}
