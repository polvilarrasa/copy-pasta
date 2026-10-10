@props(['name' => 'code', 'label'])

<x-ui.input
    :name="$name"
    :label="$label"
    inputmode="numeric"
    autocomplete="one-time-code"
    maxlength="6"
    pattern="[0-9]{6}"
    class="text-center font-bold tracking-widest"
    {{ $attributes }}
/>
