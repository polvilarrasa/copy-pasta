<?php

declare(strict_types=1);

namespace App\Http\Controllers\Impersonation;

use App\Actions\StopImpersonating;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;

class LeaveImpersonationController extends Controller
{
    public function __invoke(StopImpersonating $stopImpersonating, ImpersonateManager $impersonation): RedirectResponse
    {
        $stopImpersonating->handle($impersonation);

        return redirect()->to(url('/admin'));
    }
}
