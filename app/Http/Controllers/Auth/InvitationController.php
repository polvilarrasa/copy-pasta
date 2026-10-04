<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptInvitation;
use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, User $user): View
    {
        $this->ensureInvitationIsOpen($request, $user);

        return view('pages.auth.accept-invitation');
    }

    public function store(Request $request, User $user, AcceptInvitation $acceptInvitation): RedirectResponse
    {
        $this->ensureInvitationIsOpen($request, $user);

        $validated = $request->validate(['password' => $this->passwordRules()]);

        $acceptInvitation->handle($user, (string) $validated['password']);

        return redirect()->route('login')->with('status', __('auth.invitation.accepted'));
    }

    /**
     * The signed link is still valid for 72 hours, but it stops working as soon as the password changes, so it is
     * single-use and a later admin action cannot reopen it.
     */
    private function ensureInvitationIsOpen(Request $request, User $user): void
    {
        abort_unless(
            hash_equals($user->invitationFingerprint(), (string) $request->query('fingerprint')),
            404,
        );
    }
}
