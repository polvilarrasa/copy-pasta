@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1 text-center">
    <h1 class="text-xl text-ink">{{ $title }}</h1>
    <p class="text-base text-muted">{{ $description }}</p>
</div>
