{{-- "Añadir a carpeta" dialog. Opened by the folders button in copypasta-actions; one instance per card. --}}
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/40 p-4" x-on:click.self="close()">
        <div role="dialog" aria-modal="true" aria-labelledby="copypasta-folders-title-{{ $copypasta->getKey() }}" class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
            <h2 id="copypasta-folders-title-{{ $copypasta->getKey() }}" class="text-base font-semibold text-zinc-900">{{ __('public.folders.title') }}</h2>

            <p x-show="loading" class="mt-3 text-sm text-zinc-500" x-text="messages.loading"></p>

            <ul class="mt-3 max-h-64 space-y-2 overflow-y-auto" x-show="! loading">
                <template x-for="folder in folders" :key="folder.id">
                    <li>
                        <label class="flex items-center gap-2 text-sm text-zinc-800">
                            <input type="checkbox" class="rounded border-zinc-300" :value="String(folder.id)" x-model="selected">
                            <span x-text="folder.name"></span>
                        </label>
                    </li>
                </template>
            </ul>

            <p x-show="! loading && folders.length === 0" class="mt-3 text-sm text-zinc-500" x-text="messages.empty"></p>

            <form class="mt-4 flex gap-2" x-on:submit.prevent="createFolder()">
                <label class="sr-only" for="new-folder-{{ $copypasta->getKey() }}">{{ __('public.folders.new_placeholder') }}</label>
                <input
                    id="new-folder-{{ $copypasta->getKey() }}"
                    type="text"
                    maxlength="50"
                    x-model="newName"
                    placeholder="{{ __('public.folders.new_placeholder') }}"
                    class="min-w-0 flex-1 rounded-md border-zinc-300 text-sm"
                >
                <button type="submit" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50">{{ __('public.folders.create') }}</button>
            </form>

            <p x-show="error" x-text="error" role="alert" class="mt-3 text-sm text-rose-700"></p>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" x-on:click="close()" class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('public.folders.cancel') }}</button>
                <button type="button" x-on:click="save()" class="rounded-md bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-800">{{ __('public.folders.save') }}</button>
            </div>
        </div>
    </div>
</div>
