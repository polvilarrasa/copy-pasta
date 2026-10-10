<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        abort_if(is_impersonating(), 403);

        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        abort_if(is_impersonating(), 403);

        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        abort_if(is_impersonating(), 403);

        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    /**
     * Get the current modal configuration state.
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('settings.two_factor_setup.enabled_title'),
                'description' => __('settings.two_factor_setup.enabled_description'),
                'buttonText' => __('settings.two_factor_setup.close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('settings.two_factor_setup.verify_title'),
                'description' => __('settings.two_factor_setup.verify_description'),
                'buttonText' => __('settings.two_factor_setup.continue'),
            ];
        }

        return [
            'title' => __('settings.two_factor_setup.enable_title'),
            'description' => __('settings.two_factor_setup.enable_description'),
            'buttonText' => __('settings.two_factor_setup.continue'),
        ];
    }
}; ?>

<x-ui.modal
    id="two-factor-setup-modal"
    :title="$this->modalConfig['title']"
    close="closeModal"
    class="max-w-md"
>
    <div class="space-y-6">
        <div class="flex flex-col items-center space-y-4">
            <div class="flex size-16 items-center justify-center rounded-full bg-surface-2">
                <x-lucide-qr-code class="size-7 text-ink" aria-hidden="true" />
            </div>

            <p class="text-center text-base text-muted">{{ $this->modalConfig['description'] }}</p>
        </div>

        @if ($showVerificationStep)
            <div class="space-y-6">
                <div
                    class="flex flex-col items-center space-y-3 justify-center"
                    x-data
                    x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                >
                    <x-ui.otp
                        name="code"
                        wire:model="code"
                        :label="__('ui.otp.label')"
                        class="mx-auto max-w-40"
                    />
                </div>

                <div class="flex items-center space-x-3">
                    <x-ui.button
                        variant="secondary"
                        class="flex-1"
                        wire:click="resetVerification"
                    >
                        {{ __('settings.two_factor_setup.back') }}
                    </x-ui.button>

                    <x-ui.button
                        variant="primary"
                        class="flex-1"
                        wire:click="confirmTwoFactor"
                        x-bind:disabled="$wire.code.length < 6"
                    >
                        {{ __('settings.two_factor_setup.confirm') }}
                    </x-ui.button>
                </div>
            </div>
        @else
            @error('setupData')
                <div class="flex items-center gap-2 rounded-lg bg-bad-bg px-4 py-3 text-sm font-semibold text-bad">
                    <x-lucide-circle-x class="size-5 shrink-0" aria-hidden="true" />
                    {{ $message }}
                </div>
            @enderror

            <div class="flex justify-center">
                <div class="relative w-64 overflow-hidden rounded-lg border border-border aspect-square">
                    @empty($qrCodeSvg)
                        <div class="absolute inset-0 flex items-center justify-center bg-surface-2 animate-pulse">
                            <x-lucide-loader-circle class="size-6 animate-spin text-muted" aria-hidden="true" />
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full p-4">
                            <div class="rounded bg-white p-3" style="filter: var(--cp-qr-invert)">
                                {!! $qrCodeSvg !!}
                            </div>
                        </div>
                    @endempty
                </div>
            </div>

            <div>
                <x-ui.button
                    :disabled="$errors->has('setupData')"
                    variant="primary"
                    class="w-full"
                    wire:click="showVerificationIfNecessary"
                >
                    {{ $this->modalConfig['buttonText'] }}
                </x-ui.button>
            </div>

            <div class="space-y-4">
                <div class="relative flex items-center justify-center w-full">
                    <div class="absolute inset-0 w-full h-px top-1/2 bg-border"></div>
                    <span class="relative px-2 text-sm bg-surface text-muted">
                        {{ __('settings.two_factor_setup.manual_key') }}
                    </span>
                </div>

                <div
                    class="flex items-center space-x-2"
                    x-data="{
                        copied: false,
                        async copy() {
                            try {
                                await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                this.copied = true;
                                setTimeout(() => this.copied = false, 1500);
                            } catch (e) {
                                console.warn('Could not copy to clipboard');
                            }
                        }
                    }"
                >
                    <div class="flex items-stretch w-full rounded-xl border border-border">
                        @empty($manualSetupKey)
                            <div class="flex items-center justify-center w-full p-3 bg-surface-2">
                                <x-lucide-loader-circle class="size-4 animate-spin text-muted" aria-hidden="true" />
                            </div>
                        @else
                            <input
                                type="text"
                                readonly
                                value="{{ $manualSetupKey }}"
                                class="w-full p-3 bg-transparent outline-none text-ink"
                            />

                            <button
                                @click="copy()"
                                aria-label="{{ __('settings.two_factor_setup.copy') }}"
                                class="px-3 transition-colors border-s cursor-pointer border-border"
                            >
                                <x-lucide-copy x-show="!copied" class="size-5 text-muted" aria-hidden="true" />
                                <x-lucide-check
                                    x-show="copied"
                                    x-cloak
                                    class="size-5 text-ok"
                                    aria-hidden="true"
                                />
                            </button>
                        @endempty
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-ui.modal>
