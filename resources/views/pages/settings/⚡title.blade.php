<?php

use App\Actions\ChangeUserTitle;
use App\Enums\Achievement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Ajustes de título')] class extends Component {
    /** The chosen title key; an empty string means no title. */
    public string $titleKey = '';

    public function mount(): void
    {
        $this->titleKey = Auth::user()->titleLabel() === null ? '' : (string) Auth::user()->title_key;
    }

    /**
     * The titles of the achievements the member holds and that have not been revoked, in registry order.
     *
     * @return array<string, string> title key => title
     */
    #[Computed]
    public function options(): array
    {
        $held = Auth::user()->achievements()->whereNull('revoked_at')->pluck('achievement_key')->all();

        $options = [];

        foreach (Achievement::cases() as $achievement) {
            if ($achievement->titleKey() !== null && in_array($achievement->value, $held, true)) {
                $options[$achievement->titleKey()] = __('achievements.titles.'.$achievement->titleKey());
            }
        }

        return $options;
    }

    /**
     * Each choice saves as soon as it changes. Staff reviewing the account must not alter the member's title.
     */
    public function updatedTitleKey(): void
    {
        abort_if(is_impersonating(), 403);

        app(ChangeUserTitle::class)->handle(Auth::user(), $this->titleKey === '' ? null : $this->titleKey);

        $this->dispatch('ui-toast', message: __('achievements.settings.saved'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('achievements.settings.title') }}</h2>

    <x-pages::settings.layout :heading="__('achievements.settings.title')" :subheading="__('achievements.settings.description')">
        @if ($this->options === [])
            <p class="text-base text-muted" data-test="title-empty">{{ __('achievements.settings.empty') }}</p>
        @else
            <fieldset>
                <legend class="sr-only">{{ __('achievements.settings.legend') }}</legend>
                <div class="divide-y divide-border">
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 py-2 text-md text-ink">
                        <input
                            type="radio"
                            name="title"
                            value=""
                            wire:model.live="titleKey"
                            @disabled(is_impersonating())
                            class="size-5 shrink-0 accent-vote focus-visible:outline-none focus-visible:shadow-focus"
                        >
                        <span class="flex flex-col">
                            <span class="font-semibold">{{ __('achievements.settings.none') }}</span>
                            <span class="text-sm text-muted">{{ __('achievements.settings.none_description') }}</span>
                        </span>
                    </label>

                    @foreach ($this->options as $key => $label)
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 py-2 text-md text-ink" wire:key="title-option-{{ $key }}">
                            <input
                                type="radio"
                                name="title"
                                value="{{ $key }}"
                                wire:model.live="titleKey"
                                @disabled(is_impersonating())
                                class="size-5 shrink-0 accent-vote focus-visible:outline-none focus-visible:shadow-focus"
                            >
                            <span class="font-semibold bidi-isolate">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
    </x-pages::settings.layout>
</section>
