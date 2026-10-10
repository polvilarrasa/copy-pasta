<x-layouts::auth :title="__('settings.security.two_factor.title')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('auth.two_factor_challenge.title')"
                    :description="__('auth.two_factor_challenge.description')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('auth.two_factor_challenge.recovery_title')"
                    :description="__('auth.two_factor_challenge.recovery_description')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-5" x-ref="otp">
                            <x-ui.otp
                                x-model="code"
                                name="code"
                                :label="__('ui.otp.label')"
                                class="mx-auto max-w-40"
                             />
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <x-ui.input
                                type="text"
                                name="recovery_code"
                                label="{{ __('auth.two_factor_challenge.recovery_title') }}"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <p class="text-sm font-semibold text-bad">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-ui.button
                        variant="primary"
                        type="submit"
                        class="w-full"
                    >
                        {{ __('auth.two_factor_challenge.continue') }}
                    </x-ui.button>
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="opacity-50">{{ __('auth.two_factor_challenge.or') }}</span>
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('auth.two_factor_challenge.use_recovery') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('auth.two_factor_challenge.use_code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
