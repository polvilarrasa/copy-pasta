<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HideCopypasta
{
    public function __construct(private ConcealCopypasta $concealCopypasta) {}

    /**
     * Staff hide a copy-pasta with a mandatory reason; pending reports on it are accepted.
     */
    public function handle(User $actor, Copypasta $copypasta, string $reason): Copypasta
    {
        Gate::forUser($actor)->authorize('hide', $copypasta);

        $reason = trim($reason);

        throw_if(
            $reason === '',
            ValidationException::withMessages(['reason' => __('admin.reason_required')]),
        );

        return $this->concealCopypasta->handle($actor, $copypasta, $reason, acceptsPendingReports: true);
    }
}
