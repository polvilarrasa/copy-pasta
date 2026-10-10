@php
    $tones = [
        't1' => 'bg-t1-bg text-t1-fg',
        't2' => 'bg-t2-bg text-t2-fg',
        't3' => 'bg-t3-bg text-t3-fg',
        't4' => 'bg-t4-bg text-t4-fg',
        't5' => 'bg-t5-bg text-t5-fg',
    ];
@endphp

<section class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-extrabold text-ink">
            {{ $copypasta ? __('public.publish.title_edit') : __('public.publish.title_create') }}
        </h1>
        <a href="{{ $copypasta ? route('copypastas.mine') : route('home') }}" class="text-sm font-bold text-muted hover:text-ink">
            {{ __('public.publish.cancel') }}
        </a>
    </div>

    @if ($copypasta?->isHidden())
        <div class="rounded-2xl border border-bad-bg bg-bad-bg p-4 text-sm font-semibold text-bad">
            {{ __('app.show.hidden_notice', ['reason' => $copypasta->hidden_reason]) }}
        </div>
    @endif

    @if ($this->duplicate)
        <div class="rounded-2xl border border-border bg-surface-2 p-4 text-sm text-ink">
            <p class="font-bold">{{ __('app.duplicate.title') }}</p>
            <p class="text-muted">{{ __('app.duplicate.body') }}</p>
            <a href="{{ route('copypastas.show', [$this->duplicate, $this->duplicate->slug]) }}" class="font-bold underline">
                {{ __('app.duplicate.view') }}
            </a>
        </div>
    @endif

    <div class="flex flex-col items-start gap-10 lg:flex-row">
        <form wire:submit="save" class="flex w-full min-w-0 flex-col gap-6 lg:flex-1">
            <div class="grid gap-2">
                <label for="field-title" class="flex items-center justify-between text-sm font-semibold text-ink">
                    <span>{{ __('app.fields.title') }}</span>
                    <span class="font-semibold tabular-nums text-muted" x-text="$wire.title.length + '/120'"></span>
                </label>
                <input
                    id="field-title"
                    name="title"
                    wire:model.live="title"
                    type="text"
                    maxlength="120"
                    class="h-12 w-full rounded-lg border border-border bg-surface px-3.5 text-md text-ink placeholder:text-muted focus-visible:outline-none focus-visible:shadow-focus"
                    @if ($errors->has('title')) aria-invalid="true" @endif
                >
                @error('title')
                    <p class="text-sm font-semibold text-bad">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.textarea
                name="body"
                wire:model.live="body"
                :label="__('app.fields.body')"
                :hint="__('app.fields.body_helper')"
                rows="10"
            />

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-sm font-semibold text-ink">
                    {{ __('app.fields.tags') }}
                    <span class="font-semibold text-muted">&middot; {{ __('app.fields.tags_helper', ['max' => \App\Actions\ResolveCopypastaTags::MAX_TAGS]) }}</span>
                </legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($tags as $tag)
                        <button
                            type="button"
                            id="tag-{{ $tag->slug }}"
                            wire:click="toggleTag({{ $tag->id }})"
                            aria-pressed="{{ in_array($tag->id, $tagIds, true) ? 'true' : 'false' }}"
                            class="min-h-11 rounded-full px-4 text-sm font-bold {{ in_array($tag->id, $tagIds, true) ? $tones[$tag->color] : 'bg-surface-2 text-ink' }}"
                        >
                            #{{ $tag->name }}
                        </button>
                    @endforeach
                </div>
                @error('tag_ids')
                    <p class="text-sm font-semibold text-bad">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="rounded-2xl border border-border bg-surface px-4 py-3">
                <x-ui.switch name="is_nsfw" wire:model="isNsfw" :label="__('app.fields.is_nsfw')" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit" variant="primary" size="lg" class="flex-1" data-test="publish-submit-button">
                    {{ $copypasta ? __('public.publish.submit_edit') : __('public.publish.submit_create') }}
                </x-ui.button>

                @if ($copypasta)
                    <x-ui.button
                        type="button"
                        variant="danger"
                        x-on:click="$dispatch('open-modal', 'confirm-delete-publish')"
                    >
                        {{ __('public.publish.delete') }}
                    </x-ui.button>
                @endif
            </div>
        </form>

        <section class="flex w-full min-w-0 flex-col gap-3 lg:max-w-md lg:flex-1">
            <h2 class="flex items-center gap-2 text-sm font-bold text-ink">
                {{ __('public.publish.preview_title') }}
                <span class="rounded-full bg-surface-2 px-2 py-0.5 text-xs font-bold text-muted">{{ __('public.publish.preview_live') }}</span>
            </h2>

            <x-ui.copypasta-card
                :title="$title !== '' ? $title : __('app.fields.title')"
                :body="$body !== '' ? $body : __('app.fields.body')"
                :author="$previewAuthor"
                :author-hue="$previewAuthorHue"
                :score="$copypasta?->score ?? 0"
                :tags="$this->previewTags"
                :nsfw="$isNsfw"
            />

            <p class="text-sm text-muted">{{ __('public.publish.preview_help') }}</p>
        </section>
    </div>

    @if ($copypasta)
        <x-ui.modal id="confirm-delete-publish" :title="__('public.publish.delete')">
            {{ __('public.publish.delete_confirm') }}

            <x-slot:actions>
                <x-ui.button variant="danger" wire:click="delete" x-on:click="open = false">
                    {{ __('public.publish.delete') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.modal>
    @endif
</section>
