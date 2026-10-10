<?php

use App\Actions\UpdateNotificationPreferences;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Ajustes de notificaciones')] class extends Component {
    /** @var array<string, bool> */
    public array $prefs = [];

    public function mount(): void
    {
        foreach (NotificationType::configurable() as $type) {
            $this->prefs[$type->value] = Auth::user()->wantsNotification($type);
        }
    }

    /**
     * Each switch saves as soon as it changes. Staff reviewing the account must not alter the member's preferences.
     */
    public function updatedPrefs(): void
    {
        abort_if(is_impersonating(), 403);

        app(UpdateNotificationPreferences::class)->handle(Auth::user(), $this->prefs);

        $this->dispatch('ui-toast', message: __('settings.notifications.saved'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('settings.notifications.title') }}</h2>

    <x-pages::settings.layout :heading="__('settings.notifications.title')" :subheading="__('settings.notifications.description')">
        <div class="space-y-8">
            <fieldset>
                <legend class="text-md font-bold text-ink">{{ __('settings.notifications.optional') }}</legend>
                <div class="mt-2 divide-y divide-border">
                    @foreach (\App\Enums\NotificationType::configurable() as $type)
                        <div class="py-2">
                            <x-ui.switch
                                :name="'prefs.'.$type->value"
                                :label="__('notifications.types.'.$type->value.'.label')"
                                wire:model.live="prefs.{{ $type->value }}"
                                :disabled="is_impersonating()"
                            />
                            <p class="text-sm text-muted">{{ __('notifications.types.'.$type->value.'.description') }}</p>
                        </div>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-md font-bold text-ink">{{ __('settings.notifications.mandatory') }}</legend>
                <p class="mt-1 text-sm text-muted">{{ __('settings.notifications.mandatory_hint') }}</p>
                <div class="mt-2 divide-y divide-border">
                    @foreach (\App\Enums\NotificationType::mandatory() as $type)
                        <div class="py-2">
                            <x-ui.switch
                                :name="'mandatory.'.$type->value"
                                :label="__('notifications.types.'.$type->value.'.label')"
                                checked
                                disabled
                            />
                            <p class="text-sm text-muted">{{ __('notifications.types.'.$type->value.'.description') }}</p>
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </div>
    </x-pages::settings.layout>
</section>
