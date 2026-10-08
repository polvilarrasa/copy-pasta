<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        abort_if(is_impersonating(), 403);

        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        // The codes are the account's secret: an impersonating admin sees the component without them.
        if (is_impersonating()) {
            return;
        }

        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="py-6 space-y-6 rounded-xl border border-border shadow-day"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="px-6 space-y-2">
        <div class="flex items-center gap-2">
            <x-lucide-lock class="size-4 text-ink" aria-hidden="true" />
            <h3 class="text-lg text-ink">{{ __('2FA recovery codes') }}</h3>
        </div>
        <p class="text-base text-muted">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.button
                x-show="!showRecoveryCodes"
                variant="primary"
                @click="showRecoveryCodes = true;"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            >
                <x-lucide-eye class="size-5" aria-hidden="true" />
                {{ __('View recovery codes') }}
            </x-ui.button>

            <x-ui.button
                x-show="showRecoveryCodes"
                variant="primary"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            >
                <x-lucide-eye-off class="size-5" aria-hidden="true" />
                {{ __('Hide recovery codes') }}
            </x-ui.button>

            @if (filled($recoveryCodes))
                <x-ui.button
                    x-show="showRecoveryCodes"
                    variant="secondary"
                    wire:click="regenerateRecoveryCodes"
                >
                    <x-lucide-refresh-cw class="size-5" aria-hidden="true" />
                    {{ __('Regenerate codes') }}
                </x-ui.button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <div class="flex items-center gap-2 rounded-lg bg-bad-bg px-4 py-3 text-sm font-semibold text-bad">
                        <x-lucide-circle-x class="size-5 shrink-0" aria-hidden="true" />
                        {{ $message }}
                    </div>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 rounded-lg bg-surface-2 p-4 font-mono text-sm text-ink"
                        role="list"
                        aria-label="{{ __('Recovery codes') }}"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted">
                        {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
