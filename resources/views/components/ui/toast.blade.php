{{-- Listens for the ui-toast window event: $dispatch('ui-toast', { message: '…' }). The one toast element site-wide.
     With `actionLabel` and `action` (a function) the toast carries a button, and stays up longer to give time to press it. --}}
<div
    x-data="{ show: false, message: '', actionLabel: null, action: null, timeout: null }"
    x-on:ui-toast.window="
        message = $event.detail.message;
        actionLabel = $event.detail.actionLabel ?? null;
        action = $event.detail.action ?? null;
        show = true;
        clearTimeout(timeout);
        timeout = setTimeout(() => show = false, action ? 8000 : 2500)
    "
    x-show="show"
    x-cloak
    role="status"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4"
>
    <div class="flex items-center gap-4 rounded-xl bg-accent px-4 py-3 text-md font-semibold text-on-accent shadow-pop">
        <span x-text="message"></span>
        <button
            type="button"
            x-show="actionLabel"
            x-text="actionLabel"
            x-on:click="const run = action; show = false; run && run()"
            class="pointer-events-auto min-h-11 rounded-md px-2 font-extrabold underline focus-visible:outline-none focus-visible:shadow-focus"
        ></button>
    </div>
</div>
