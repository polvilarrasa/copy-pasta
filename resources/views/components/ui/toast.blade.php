{{-- Listens for the ui-toast window event: $dispatch('ui-toast', { message: '…' }). The one toast element site-wide. --}}
<div
    x-data="{ show: false, message: '', timeout: null }"
    x-on:ui-toast.window="message = $event.detail.message; show = true; clearTimeout(timeout); timeout = setTimeout(() => show = false, 2500)"
    x-show="show"
    x-cloak
    role="status"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4"
>
    <div class="rounded-xl bg-accent px-4 py-3 text-md font-semibold text-on-accent shadow-pop" x-text="message"></div>
</div>
