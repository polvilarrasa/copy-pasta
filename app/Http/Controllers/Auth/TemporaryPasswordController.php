<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\ChangeTemporaryPassword;
use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemporaryPasswordController extends Controller
{
    use PasswordValidationRules;

    public function edit(): View
    {
        return view('pages.auth.temporary-password');
    }

    public function update(Request $request, ChangeTemporaryPassword $changeTemporaryPassword): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => [...$this->passwordRules(), 'different:current_password'],
        ]);

        $user = $request->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        $changeTemporaryPassword->handle($user, (string) $validated['password']);

        return redirect()->intended(url('/app'))->with('status', __('auth.temporary_password.changed'));
    }
}
