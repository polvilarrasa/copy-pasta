@props(['copypasta', 'parent' => null, 'variants', 'variantsTotal', 'context' => []])

{{--
    The card of the copy-pasta with its family: "Variante de…" above it when it is a variant and, below, one tab per
    direct variant (best score first) next to the copy-pasta itself. Without visible variants it is the card alone.
--}}
@php
    $canCreateVariant = auth()->check() && auth()->user()->can('createVariant', $copypasta);
    $parentAvailable = $parent !== null && $parent->isAvailable();
    $tabs = [['id' => 'original', 'label' => $parent ? __('public.variants.this_version') : __('public.variants.original')]];

    foreach ($variants as $index => $variant) {
        $tabs[] = [
            'id' => 'v'.($index + 1),
            'label' => $variant->user->isAnonymized() ? __('public.variants.deleted_author') : '@'.$variant->user->username,
        ];
    }
@endphp

@if ($parent)
    <p class="flex flex-wrap items-center gap-1.5 px-1 text-sm text-muted" data-test="variant-of">
        <x-lucide-git-fork class="size-4" aria-hidden="true" />
        <span>{{ __('public.variants.variant_of') }}</span>
        @if ($parentAvailable)
            <a href="{{ route('copypastas.show', [$parent, $parent->slug]) }}" class="font-bold text-ink underline bidi-isolate">{{ $parent->title }}</a>
        @else
            <span class="font-bold">{{ __('public.variants.removed') }}</span>
        @endif
    </p>
@endif

@if ($variants->isEmpty())
    <x-copypasta-card :copypasta="$copypasta" :context="$context" :full="true" />

    @if ($canCreateVariant)
        <div>
            <a
                href="{{ route('copypastas.variant', $copypasta) }}"
                data-test="create-variant"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-surface-2 px-4 text-md font-bold text-ink focus-visible:outline-none focus-visible:shadow-focus"
            >
                <x-lucide-git-fork class="size-5" aria-hidden="true" />
                {{ __('public.variants.create') }}
            </a>
        </div>
    @endif
@else
    <section id="variantes" class="flex flex-col gap-3" aria-labelledby="variants-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="variants-heading" class="flex items-center gap-2 text-base font-extrabold text-ink">
                {{ __('public.variants.heading') }}
                <span class="rounded-full bg-surface-2 px-2 py-0.5 text-xs font-bold text-muted">{{ $variantsTotal }}</span>
            </h2>

            @if ($canCreateVariant)
                <a
                    href="{{ route('copypastas.variant', $copypasta) }}"
                    data-test="create-variant"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-surface-2 px-4 text-md font-bold text-ink focus-visible:outline-none focus-visible:shadow-focus"
                >
                    <x-lucide-git-fork class="size-5" aria-hidden="true" />
                    {{ __('public.variants.create') }}
                </a>
            @endif
        </div>

        <x-ui.tabs id="variants" :label="__('public.variants.tabs_label')" :tabs="$tabs">
            <x-ui.tab-panel prefix="variants" tab="original">
                <x-copypasta-card :copypasta="$copypasta" :context="$context" :full="true" />
            </x-ui.tab-panel>

            @foreach ($variants as $index => $variant)
                <x-ui.tab-panel prefix="variants" :tab="'v'.($index + 1)">
                    <x-copypasta-card :copypasta="$variant" source="variants" :position="$index" :full="true" />
                </x-ui.tab-panel>
            @endforeach
        </x-ui.tabs>

        @if ($variantsTotal > $variants->count())
            <p class="px-1 text-sm text-muted" data-test="variants-more">
                {{ trans_choice('public.variants.more', $variantsTotal - $variants->count(), ['count' => $variantsTotal - $variants->count()]) }}
            </p>
        @endif
    </section>
@endif
