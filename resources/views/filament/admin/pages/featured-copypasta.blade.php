<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('admin.featured.today') }}</x-slot>

        @if ($featured === null)
            <p>{{ __('admin.featured.none') }}</p>
        @else
            @php($copypasta = $featured->copypasta)

            <dl class="grid gap-2 text-sm">
                <div>
                    <dt class="font-semibold">{{ __('admin.fields.title') }}</dt>
                    <dd>{{ $copypasta->title }}</dd>
                </div>
                <div>
                    <dt class="font-semibold">{{ __('admin.featured.picked_by') }}</dt>
                    <dd>{{ $featured->pickedBy?->username ?? __('admin.featured.automatic') }}</dd>
                </div>
                <div>
                    <dt class="font-semibold">{{ __('admin.fields.status') }}</dt>
                    <dd>
                        @if ($copypasta->trashed())
                            {{ __('admin.users.copypasta_status.deleted') }}
                        @elseif ($copypasta->isHidden() || $copypasta->is_nsfw)
                            {{ __('admin.featured.not_shown') }}
                        @else
                            {{ __('admin.featured.shown') }}
                        @endif
                    </dd>
                </div>
            </dl>
        @endif
    </x-filament::section>
</x-filament-panels::page>
