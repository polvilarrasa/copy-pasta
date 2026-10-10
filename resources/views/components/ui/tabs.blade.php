@props(['id', 'label', 'tabs', 'selected' => null])

{{-- Each panel is an x-ui.tab-panel with the same prefix. Arrow keys move and select, Home and End jump to the ends. --}}
<div x-data="{ selected: @js($selected ?? $tabs[0]['id']) }" {{ $attributes }}>
    <div x-ref="list" role="tablist" aria-label="{{ $label }}" class="flex gap-1 overflow-x-auto rounded-full bg-surface-2 p-1">
        @foreach ($tabs as $tab)
            <button
                type="button"
                role="tab"
                id="{{ $id }}-tab-{{ $tab['id'] }}"
                aria-controls="{{ $id }}-panel-{{ $tab['id'] }}"
                :aria-selected="selected === '{{ $tab['id'] }}' ? 'true' : 'false'"
                :tabindex="selected === '{{ $tab['id'] }}' ? 0 : -1"
                x-on:click="selected = '{{ $tab['id'] }}'"
                x-on:focus="selected = '{{ $tab['id'] }}'"
                x-on:keydown.right.prevent="$focus.within($refs.list).wrap().next()"
                x-on:keydown.left.prevent="$focus.within($refs.list).wrap().previous()"
                x-on:keydown.home.prevent="$focus.within($refs.list).first()"
                x-on:keydown.end.prevent="$focus.within($refs.list).last()"
                :class="selected === '{{ $tab['id'] }}' ? 'bg-accent text-on-accent' : 'text-muted'"
                class="min-h-11 shrink-0 rounded-full px-4 text-sm font-semibold focus-visible:outline-none focus-visible:shadow-focus"
            >{{ $tab['label'] }}</button>
        @endforeach
    </div>
    <div class="mt-4">{{ $slot }}</div>
</div>
