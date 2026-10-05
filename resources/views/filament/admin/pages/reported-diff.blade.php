<div class="space-y-6 text-sm">
    @forelse ($sections as $section)
        <section class="space-y-3">
            <p class="text-zinc-500">{{ __('moderation.queue.version_from', ['date' => $section['date']->diffForHumans()]) }}</p>

            <h3 class="font-semibold">
                @foreach ($section['title'] as $part)
                    @if ($part['type'] === 'equal')
                        <span>{{ $part['text'] }}</span>
                    @elseif ($part['type'] === 'delete')
                        <del class="rounded bg-red-100 px-0.5 text-red-700">{{ $part['text'] }}</del>
                    @else
                        <ins class="rounded bg-green-100 px-0.5 text-green-700 no-underline">{{ $part['text'] }}</ins>
                    @endif
                @endforeach
            </h3>

            <p class="whitespace-pre-wrap">
                @foreach ($section['body'] as $part)
                    @if ($part['type'] === 'equal')
                        <span>{{ $part['text'] }}</span>
                    @elseif ($part['type'] === 'delete')
                        <del class="rounded bg-red-100 px-0.5 text-red-700">{{ $part['text'] }}</del>
                    @else
                        <ins class="rounded bg-green-100 px-0.5 text-green-700 no-underline">{{ $part['text'] }}</ins>
                    @endif
                @endforeach
            </p>
        </section>
    @empty
        <p class="text-zinc-500">{{ __('moderation.queue.empty') }}</p>
    @endforelse
</div>
