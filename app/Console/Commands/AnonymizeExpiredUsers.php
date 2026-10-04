<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AnonymizeUser;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:anonymize-expired')]
#[Description('Anonimiza las cuentas borradas por un admin hace más de 30 días')]
class AnonymizeExpiredUsers extends Command
{
    public const RETENTION_DAYS = 30;

    public function handle(AnonymizeUser $anonymizeUser): int
    {
        $anonymized = 0;

        User::onlyTrashed()
            ->whereNull('anonymized_at')
            ->where('deleted_at', '<=', now()->subDays(self::RETENTION_DAYS))
            ->lazyById()
            ->each(function (User $user) use ($anonymizeUser, &$anonymized): void {
                $anonymizeUser->handle($user, null);
                $anonymized++;
            });

        $this->info("Cuentas anonimizadas: {$anonymized}.");

        return self::SUCCESS;
    }
}
