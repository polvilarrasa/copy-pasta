@php
    /** @var \App\Support\ProfileAchievements $achievements */
    $tones = [
        't1' => 'bg-t1-bg text-t1-fg',
        't2' => 'bg-t2-bg text-t2-fg',
        't3' => 'bg-t3-bg text-t3-fg',
        't4' => 'bg-t4-bg text-t4-fg',
        't5' => 'bg-t5-bg text-t5-fg',
    ];
@endphp

<section id="logros" class="flex scroll-mt-6 flex-col gap-8" aria-labelledby="achievements-heading" data-test="profile-achievements">
    <div class="flex flex-col gap-3.5">
        <h2 id="achievements-heading" class="flex items-center gap-2.5 text-xl font-extrabold text-ink">
            {{ __('achievements.profile.earned') }}
            <span class="rounded-full bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-muted">{{ count($achievements->earned) }}</span>
        </h2>

        @if ($achievements->earned === [])
            <p class="text-base text-muted">{{ $achievements->isOwner ? __('achievements.profile.empty_own') : __('achievements.profile.empty') }}</p>
        @else
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($achievements->earned as $entry)
                    <li data-test="achievement-earned" class="flex items-center gap-3.5 rounded-2xl border border-border bg-surface p-3.5">
                        <span aria-hidden="true" class="flex size-14 shrink-0 items-center justify-center rounded-2xl {{ $tones[$entry->achievement->tone()] }}">
                            <x-dynamic-component :component="'lucide-'.$entry->achievement->icon()" class="size-7" />
                        </span>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="flex flex-wrap items-center gap-2 text-base font-extrabold text-ink">
                                {{ $entry->achievement->name() }}
                                @if ($entry->isActiveTitle)
                                    <span class="rounded-full bg-accent px-2 py-0.5 text-xs font-extrabold text-on-accent">{{ __('achievements.profile.active_title') }}</span>
                                @endif
                            </span>
                            <span class="text-sm text-muted">{{ $entry->achievement->description() }}</span>
                            <span class="text-xs font-semibold text-muted">
                                {{ __('achievements.profile.unlocked_on', ['date' => $entry->unlockedAt?->translatedFormat('j \d\e F \d\e Y')]) }}
                                @if ($entry->rarity)
                                    · {{ __('achievements.rarity.label', ['rarity' => $entry->rarity]) }}
                                @endif
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (! $achievements->isOwner && $achievements->secretsEarned > 0)
            <p data-test="achievements-secrets-earned" class="text-sm font-semibold text-muted">
                {{ trans_choice('achievements.profile.secrets_earned', $achievements->secretsEarned, ['count' => $achievements->secretsEarned]) }}
            </p>
        @endif
    </div>

    @if ($achievements->isOwner)
        <div class="flex flex-col gap-3.5" data-test="achievements-pending">
            <h2 class="flex items-center gap-2.5 text-xl font-extrabold text-ink">
                {{ __('achievements.profile.pending') }}
                <span class="rounded-full bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-muted">{{ count($achievements->pending) }}</span>
            </h2>

            <ul class="flex flex-col gap-3">
                @foreach ($achievements->pending as $entry)
                    <li class="flex items-center gap-3.5 rounded-2xl border border-border bg-surface p-3.5">
                        <span aria-hidden="true" class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-surface-2 text-muted">
                            <x-dynamic-component :component="'lucide-'.$entry->achievement->icon()" class="size-7" />
                        </span>
                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <span class="text-base font-extrabold text-ink">{{ $entry->achievement->name() }}</span>
                            <span class="text-sm text-muted">{{ $entry->achievement->description() }}</span>
                            <div class="flex items-center gap-2.5">
                                <div
                                    role="progressbar"
                                    aria-label="{{ __('achievements.profile.progress_label', ['name' => $entry->achievement->name()]) }}"
                                    aria-valuemin="0"
                                    aria-valuemax="{{ $entry->max }}"
                                    aria-valuenow="{{ $entry->current }}"
                                    class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2"
                                >
                                    <div class="h-full rounded-full bg-vote" style="width: {{ $entry->percent() }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-muted tabular-nums">{{ __('achievements.profile.progress', ['current' => number_format($entry->current, 0, ',', '.'), 'max' => number_format($entry->max, 0, ',', '.')]) }}</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($achievements->hiddenSecrets > 0)
            <div class="flex flex-col gap-3.5" data-test="achievements-secrets">
                <h2 class="flex items-center gap-2.5 text-xl font-extrabold text-ink">
                    {{ __('achievements.profile.secrets') }}
                    <span class="rounded-full bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-muted">{{ $achievements->hiddenSecrets }}</span>
                </h2>

                <ul class="flex flex-col gap-3">
                    @for ($i = 0; $i < $achievements->hiddenSecrets; $i++)
                        <li class="flex items-center gap-3.5 rounded-2xl border-2 border-dashed border-border p-3.5">
                            <span aria-hidden="true" class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-surface-2 text-muted">
                                <x-lucide-lock class="size-6" />
                            </span>
                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span class="text-base font-extrabold tracking-widest text-ink">{{ __('achievements.profile.secret_name') }}</span>
                                <span class="text-sm text-muted">{{ __('achievements.profile.secret_description') }}</span>
                            </div>
                        </li>
                    @endfor
                </ul>
            </div>
        @endif
    @endif
</section>
