<?php

use App\Actions\ChangeUsername;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $username = '';
    public string $email = '';

    public bool $showNsfw = false;

    public bool $ageConfirmed = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->username = Auth::user()->username;
        $this->email = Auth::user()->email;
        $this->showNsfw = Auth::user()->show_nsfw;
    }

    /**
     * Turning the adult-content preference on needs the +18 confirmation, and the date of that confirmation is kept.
     */
    public function updateNsfwPreference(): void
    {
        $user = Auth::user();

        if (! $this->showNsfw) {
            $user->forceFill(['show_nsfw' => false])->save();
            $this->ageConfirmed = false;

            Flux::toast(variant: 'success', text: __('Profile updated.'));

            return;
        }

        if (! $user->show_nsfw) {
            $this->validate(['ageConfirmed' => ['accepted']]);

            $user->forceFill(['show_nsfw' => true, 'nsfw_confirmed_at' => now()])->save();
        }

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        abort_if(is_impersonating() && $validated['email'] !== $user->email, 403);

        app(ChangeUsername::class)->handle($user, $validated['username']);

        $user->email = $validated['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Profile settings') }}</h2>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-ui.input wire:model="username" name="username" :label="__('auth.username')" type="text" required autofocus autocomplete="username" />

            <div>
                <x-ui.input wire:model="email" name="email" :label="__('Email')" type="email" required autocomplete="email" :disabled="is_impersonating()" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <p class="mt-4 text-base text-muted">
                            {{ __('Your email address is unverified.') }}

                            <button type="button" class="cursor-pointer text-sm font-semibold text-ink underline" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-semibold text-ok">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-ui.button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </x-ui.button>
                </div>

            </div>
        </form>

        <form wire:submit="updateNsfwPreference" class="my-6 w-full space-y-6" data-test="nsfw-preference-form">
            <x-ui.switch wire:model="showNsfw" name="showNsfw" :label="__('app.profile.show_nsfw')" />
            <p class="-mt-4 text-sm text-muted">{{ __('app.profile.show_nsfw_helper') }}</p>

            <x-ui.checkbox wire:model="ageConfirmed" name="ageConfirmed" :label="__('app.profile.nsfw_age_confirm')" />

            <div class="flex items-center justify-end">
                <x-ui.button variant="primary" type="submit" data-test="update-nsfw-button">
                    {{ __('Save') }}
                </x-ui.button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
