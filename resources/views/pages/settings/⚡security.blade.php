<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        abort_if(is_impersonating(), 403);

        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('ui-toast', message: __('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        abort_if(is_impersonating(), 403);

        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        abort_if(is_impersonating(), 403);

        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_if(is_impersonating(), 403);

        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-ui.password-input
                wire:model="current_password"
                name="current_password"
                :label="__('Current password')"
                required
                autocomplete="current-password"
            />
            <x-ui.password-input
                wire:model="password"
                name="password"
                :label="__('New password')"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />
            <x-ui.password-input
                wire:model="password_confirmation"
                name="password_confirmation"
                :label="__('Confirm password')"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            />

            <div class="flex items-center gap-4">
                <x-ui.button variant="primary" type="submit" data-test="update-password-button">
                    {{ __('Save') }}
                </x-ui.button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h3 class="text-lg text-ink">{{ __('Two-factor authentication') }}</h3>
                <p class="text-base text-muted">{{ __('Manage your two-factor authentication settings') }}</p>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p class="text-base text-muted">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <x-ui.button
                                    variant="danger"
                                    wire:click="disable"
                                >
                                    {{ __('Disable 2FA') }}
                                </x-ui.button>
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-base text-muted">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <x-ui.button
                                variant="primary"
                                wire:click="$dispatch('start-two-factor-setup')"
                                x-on:click="$dispatch('open-modal', 'two-factor-setup-modal')"
                            >
                                {{ __('Enable 2FA') }}
                            </x-ui.button>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-12">
                <h3 class="text-lg text-ink">{{ __('Passkeys') }}</h3>
                <p class="text-base text-muted">{{ __('Manage your passkeys for passwordless sign-in') }}</p>

                <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    <div class="rounded-lg border border-border overflow-hidden">
                        @forelse ($passkeys as $passkey)
                            <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-border' : '' }}">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-surface-2">
                                        <x-lucide-key class="size-5 text-muted" aria-hidden="true" />
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2.5">
                                            <p class="font-semibold tracking-tight text-ink">{{ $passkey['name'] }}</p>
                                            @if ($passkey['authenticator'])
                                                <span class="rounded-full bg-surface-2 px-2 py-0.5 text-xs font-bold text-muted">{{ $passkey['authenticator'] }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-muted">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                <span class="opacity-50 mx-1">/</span>
                                                {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                    aria-label="{{ __('Remove passkey') }}"
                                    class="flex size-11 items-center justify-center rounded-lg text-bad hover:bg-bad-bg focus-visible:outline-none focus-visible:shadow-focus"
                                >
                                    <x-lucide-trash-2 class="size-5" aria-hidden="true" />
                                </button>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-surface-2">
                                    <x-lucide-key class="size-7 text-muted" aria-hidden="true" />
                                </div>
                                <p class="font-semibold text-ink">{{ __('No passkeys yet') }}</p>
                                <p class="mt-1 text-base text-muted">{{ __('Add a passkey to sign in without a password') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <x-passkey-registration />
                </div>
            </section>
        @endif
    </x-pages::settings.layout>

    <x-ui.modal
        id="delete-passkey-modal"
        :title="__('Remove passkey')"
        wire-model="showDeleteModal"
        close="closeDeleteModal"
        class="max-w-md"
    >
        {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}

        <x-slot:actions>
            <x-ui.button variant="danger" wire:click="deletePasskey">
                {{ __('Remove passkey') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</section>
