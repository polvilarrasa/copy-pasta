@props(['copypasta', 'context' => []])

@php
    $author = $copypasta->user === null || $copypasta->user->isAnonymized()
        ? __('public.share_image.deleted_author')
        : '@'.$copypasta->user->username;
@endphp

{{-- The image is drawn in the browser (resources/js/share-image.js); the server only supplies the text and the strings. --}}
<section
    x-data="shareImage({
        title: @js($copypasta->title),
        body: @js($copypasta->body),
        author: @js($author),
        brand: @js(__('public.share_image.brand')),
        styleLabels: @js(__('public.share_image.styles')),
        slug: @js($copypasta->slug),
        nsfw: @js((bool) $copypasta->is_nsfw),
        shareEventUrl: @js(route('copypastas.share', $copypasta)),
        shareTitle: @js($copypasta->title),
        context: @js($context),
        sharedMessage: @js(__('public.share_image.shared')),
        downloadedMessage: @js(__('public.share_image.downloaded')),
        failedMessage: @js(__('public.share_image.failed')),
    })"
    class="rounded-2xl border border-border bg-surface"
    data-test="share-image"
>
    <button
        type="button"
        x-on:click="toggle()"
        :aria-expanded="expanded ? 'true' : 'false'"
        aria-controls="share-image-panel"
        data-test="share-image-toggle"
        class="flex min-h-11 w-full items-center gap-3 rounded-2xl px-4 py-3 text-start focus-visible:outline-none focus-visible:shadow-focus"
    >
        <x-lucide-image class="size-5 shrink-0 text-muted" aria-hidden="true" />
        <span class="flex-1">
            <span class="block font-bold text-ink">{{ __('public.share_image.toggle') }}</span>
            <span class="block text-sm text-muted">{{ __('public.share_image.hint') }}</span>
        </span>
        <x-lucide-chevron-down class="size-5 shrink-0 text-muted transition-transform" x-bind:class="expanded && 'rotate-180'" aria-hidden="true" />
    </button>

    <div id="share-image-panel" x-show="expanded" x-cloak class="flex flex-col gap-4 border-t border-border p-4">
        <div x-show="! confirmed" class="flex flex-col items-start gap-3 rounded-xl bg-warn-bg p-4 text-warn" data-test="share-image-nsfw">
            <p class="font-bold">{{ __('public.share_image.nsfw_title') }}</p>
            <p class="text-sm">{{ __('public.share_image.nsfw_body') }}</p>
            <x-ui.button variant="primary" x-on:click="confirm()" data-test="share-image-confirm">{{ __('public.share_image.nsfw_confirm') }}</x-ui.button>
        </div>

        <div x-show="confirmed" class="flex flex-col gap-4">
            <canvas
                x-ref="canvas"
                role="img"
                aria-label="{{ __('public.share_image.canvas_label') }}"
                class="aspect-square w-full max-w-md self-center rounded-2xl border border-border"
            ></canvas>

            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-bold text-muted">{{ __('public.share_image.style') }}</span>
                <template x-for="key in styles" :key="key">
                    <button
                        type="button"
                        x-on:click="pick(key)"
                        :aria-pressed="style === key ? 'true' : 'false'"
                        :aria-label="styleLabels[key]"
                        :data-test="'share-image-style-' + key"
                        :style="{ backgroundColor: swatches[key] }"
                        class="size-11 rounded-full border-2 border-border focus-visible:outline-none focus-visible:shadow-focus"
                        :class="style === key && 'ring-2 ring-vote ring-offset-2 ring-offset-surface'"
                    ></button>
                </template>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="primary" size="md" x-on:click="download()" x-bind:disabled="busy" data-test="share-image-download">
                    <x-lucide-download class="size-5" aria-hidden="true" />
                    {{ __('public.share_image.download') }}
                </x-ui.button>
                <x-ui.button x-show="canShareFile()" x-on:click="share()" x-bind:disabled="busy" data-test="share-image-share">
                    <x-lucide-share-2 class="size-5" aria-hidden="true" />
                    {{ __('public.share_image.share') }}
                </x-ui.button>
            </div>
        </div>
    </div>
</section>
