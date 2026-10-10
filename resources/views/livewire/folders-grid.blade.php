<section class="mx-auto w-full max-w-3xl space-y-5 px-4 py-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('public.folder.index_title') }}</h1>
        <x-ui.button variant="primary" x-on:click="$dispatch('open-modal', 'create-folder')">
            {{ __('app.folders.create') }}
        </x-ui.button>
    </div>

    @if ($folders->isEmpty())
        <x-ui.empty-state :title="__('public.folder.index_empty_title')">
            {{ __('public.folder.index_empty_body') }}
        </x-ui.empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($folders as $folder)
                <article class="flex flex-col gap-3 rounded-3xl border border-border bg-surface p-4" wire:key="folder-{{ $folder->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('folders.show', $folder) }}" class="min-w-0 flex-1">
                            <h2 class="truncate text-lg font-bold text-ink">{{ $folder->name }}</h2>
                            <p class="text-sm font-semibold text-muted">{{ trans_choice('public.folder.count', $folder->copypastas_count, ['count' => $folder->copypastas_count]) }}</p>
                        </a>

                        <x-ui.dropdown :label="__('ui.card.more')">
                            <x-slot:trigger>
                                <span class="flex size-11 items-center justify-center rounded-full text-muted hover:bg-surface-2" aria-hidden="true">
                                    <x-lucide-ellipsis class="size-5" />
                                </span>
                            </x-slot:trigger>

                            <x-ui.menu-item x-on:click="$wire.startEdit({{ $folder->id }}); $dispatch('open-modal', 'edit-folder')">
                                {{ __('public.folder.edit') }}
                            </x-ui.menu-item>
                            <x-ui.menu-item wire:click="moveUp({{ $folder->id }})">
                                {{ __('public.folder.move_up') }}
                            </x-ui.menu-item>
                            <x-ui.menu-item wire:click="moveDown({{ $folder->id }})">
                                {{ __('public.folder.move_down') }}
                            </x-ui.menu-item>
                            @unless ($folder->is_default)
                                <x-ui.menu-item x-on:click="$wire.confirmDelete({{ $folder->id }}); $dispatch('open-modal', 'confirm-delete-folder')">
                                    {{ __('app.folders.delete') }}
                                </x-ui.menu-item>
                            @endunless
                        </x-ui.dropdown>
                    </div>

                    @if ($folder->description)
                        <p class="text-sm text-muted">{{ $folder->description }}</p>
                    @endif

                    <p class="truncate text-sm text-muted">
                        {{ $folder->copypastas->first()?->title ?? __('app.folders.empty') }}
                    </p>
                </article>
            @endforeach
        </div>
    @endif

    <x-ui.modal id="create-folder" :title="__('app.folders.create')">
        <form wire:submit="createFolder" class="flex flex-col gap-4 text-left">
            <x-ui.input name="name" wire:model="newFolderName" :label="__('app.fields.folder_name')" />
            <x-ui.button type="submit" variant="primary">{{ __('public.folders.create') }}</x-ui.button>
        </form>
    </x-ui.modal>

    <x-ui.modal id="edit-folder" :title="__('public.folder.edit')" :close="'resetEdit'">
        <form wire:submit="saveEdit" class="flex flex-col gap-4 text-left">
            @if ($editingFolderId !== null && ! $folders->firstWhere('id', $editingFolderId)?->is_default)
                <x-ui.input name="editingName" wire:model="editingName" :label="__('app.fields.folder_name')" />
            @endif
            <x-ui.textarea
                name="editingDescription"
                wire:model="editingDescription"
                :label="__('public.folder.description_label')"
                :hint="__('public.folder.description_hint')"
                rows="3"
                maxlength="280"
            />
            <x-ui.button type="submit" variant="primary" x-on:click="open = false">{{ __('public.folders.save') }}</x-ui.button>
        </form>
    </x-ui.modal>

    <x-ui.modal id="confirm-delete-folder" :title="__('app.folders.delete')">
        {{ __('app.folders.delete_confirm') }}

        <x-slot:actions>
            <x-ui.button variant="danger" wire:click="deleteFolder" x-on:click="open = false">
                {{ __('app.folders.delete') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</section>
