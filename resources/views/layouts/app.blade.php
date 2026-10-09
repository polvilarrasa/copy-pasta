<x-layouts::app.sidebar :title="$title ?? null">
    <main class="flex-1">
        {{ $slot }}
    </main>
</x-layouts::app.sidebar>
