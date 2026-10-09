{{-- "Añadir a carpeta" dialog. Opened by the "⋯" menu in ui.copypasta-card; one instance per card. --}}
<div
    x-data="copypastaFolders({
        copypastaId: @js($copypasta->getKey()),
        indexUrl: @js(route('copypastas.folders.index', $copypasta)),
        syncUrl: @js(route('copypastas.folders.sync', $copypasta)),
        storeUrl: @js(route('copypastas.folders.store', $copypasta)),
        messages: {
            loading: @js(__('public.folders.loading')),
            empty: @js(__('public.folders.empty')),
            failed: @js(__('public.copy.action_failed')),
            saved: @js(__('public.folders.saved')),
        },
    })"
    x-on:folders-open.window="$event.detail.copypasta === copypastaId && open($event.detail.context)"
    x-show="visible"
    x-cloak
    x-on:keydown.escape.window="close()"
>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-scrim p-4" x-on:click.self="close()">
        <div role="dialog" aria-modal="true" aria-labelledby="copypasta-folders-title-{{ $copypasta->getKey() }}" class="w-full max-w-sm rounded-3xl border border-border bg-surface p-5 text-ink shadow-pop">
            <h2 id="copypasta-folders-title-{{ $copypasta->getKey() }}" class="text-xl font-extrabold">{{ __('public.folders.title') }}</h2>

            <p x-show="loading" class="mt-3 text-base text-muted" x-text="messages.loading"></p>

            <ul class="mt-3 max-h-64 space-y-1 overflow-y-auto" x-show="! loading">
                <template x-for="folder in folders" :key="folder.id">
                    <li>
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-md text-ink">
                            <input type="checkbox" class="size-5 shrink-0 accent-vote focus-visible:outline-none focus-visible:shadow-focus" :value="String(folder.id)" x-model="selected">
                            <span x-text="folder.name"></span>
                        </label>
                    </li>
                </template>
            </ul>

            <p x-show="! loading && folders.length === 0" class="mt-3 text-base text-muted" x-text="messages.empty"></p>

            <form class="mt-4 flex gap-2" x-on:submit.prevent="createFolder()">
                <label class="sr-only" for="new-folder-{{ $copypasta->getKey() }}">{{ __('public.folders.new_placeholder') }}</label>
                <input
                    id="new-folder-{{ $copypasta->getKey() }}"
                    type="text"
                    maxlength="50"
                    x-model="newName"
                    placeholder="{{ __('public.folders.new_placeholder') }}"
                    class="h-12 min-w-0 flex-1 rounded-lg border border-border bg-surface px-3.5 text-md text-ink placeholder:text-muted focus-visible:outline-none focus-visible:shadow-focus"
                >
                <x-ui.button type="submit" variant="secondary">{{ __('public.folders.create') }}</x-ui.button>
            </form>

            <p x-show="error" x-text="error" role="alert" class="mt-3 text-sm font-semibold text-bad"></p>

            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="ghost" x-on:click="close()">{{ __('public.folders.cancel') }}</x-ui.button>
                <x-ui.button type="button" variant="primary" x-on:click="save()" data-test="folders-save-button">{{ __('public.folders.save') }}</x-ui.button>
            </div>
        </div>
    </div>
</div>
