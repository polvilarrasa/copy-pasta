{{-- Fixed band shown on every page while an admin acts as a member. Hidden otherwise. --}}
@impersonating
    <div role="status" class="sticky top-0 z-[60] flex flex-wrap items-center justify-center gap-3 bg-warn-bg px-4 py-2 text-sm font-semibold text-warn">
        <span>{{ __('moderation.impersonation.banner', ['username' => auth()->user()->username]) }}</span>
        <form method="POST" action="{{ route('impersonation.leave') }}">
            @csrf
            <button type="submit" class="rounded-md bg-ink px-3 py-1 text-xs font-bold text-surface">{{ __('moderation.impersonation.leave') }}</button>
        </form>
    </div>
@endImpersonating
