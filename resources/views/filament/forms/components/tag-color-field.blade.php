{{--
    Radio group showing the real light-mode swatch of each design tone (t1-t5), so staff pick a color by seeing
    what the public tag chip will actually look like, not by a color name.
--}}
@php
    $swatches = [
        't1' => ['bg' => '#CFF5E3', 'fg' => '#0B5A3C'],
        't2' => ['bg' => '#FFE0C9', 'fg' => '#8A3A07'],
        't3' => ['bg' => '#E0D9FF', 'fg' => '#40259E'],
        't4' => ['bg' => '#FAF0B5', 'fg' => '#5E4D00'],
        't5' => ['bg' => '#FFD9EA', 'fg' => '#99245F'],
    ];
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="flex flex-wrap gap-3" role="radiogroup" aria-label="{{ $getLabel() }}">
        @foreach ($swatches as $value => $colors)
            <label class="cursor-pointer">
                <input
                    type="radio"
                    value="{{ $value }}"
                    wire:model="{{ $statePath }}"
                    class="sr-only peer"
                >
                <span
                    class="inline-flex items-center rounded-full border-2 border-transparent px-3 py-1.5 text-sm font-bold peer-checked:border-gray-900 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-gray-900"
                    style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};"
                >#{{ __('admin.tag_colors.'.$value) }}</span>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
