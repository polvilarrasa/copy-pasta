@props(['label'])

{{-- Arrow keys move through the items, Escape closes and returns focus to the trigger, Tab closes the menu. --}}
<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.stop="open = false; $refs.trigger.focus()"
    {{ $attributes->class(['relative inline-block']) }}
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = ! open; if (open) $nextTick(() => $focus.within($refs.menu).first())"
        x-on:keydown.down.prevent="open = true; $nextTick(() => $focus.within($refs.menu).first())"
        aria-haspopup="menu"
        :aria-expanded="open ? 'true' : 'false'"
        class="rounded-full focus-visible:outline-none focus-visible:shadow-focus"
    >{{ $trigger }}</button>

    <div
        x-ref="menu"
        x-show="open"
        x-cloak
        role="menu"
        aria-label="{{ $label }}"
        x-on:keydown.down.prevent="$focus.within($refs.menu).wrap().next()"
        x-on:keydown.up.prevent="$focus.within($refs.menu).wrap().previous()"
        x-on:keydown.home.prevent="$focus.within($refs.menu).first()"
        x-on:keydown.end.prevent="$focus.within($refs.menu).last()"
        x-on:keydown.tab="open = false"
        class="absolute end-0 z-40 mt-2 min-w-48 rounded-xl border border-border bg-surface p-1.5 shadow-pop"
    >
        {{ $slot }}
    </div>
</div>
