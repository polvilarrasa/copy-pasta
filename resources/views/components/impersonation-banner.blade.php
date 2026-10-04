{{-- Fixed band shown on every page while an admin acts as a member. Hidden otherwise. --}}
@impersonating
    <div role="status" class="sticky top-0 z-[60] flex flex-wrap items-center justify-center gap-3 bg-amber-400 px-4 py-2 text-sm font-medium text-zinc-900">
        <span>{{ __('moderation.impersonation.banner', ['username' => auth()->user()->username]) }}</span>
        <form method="POST" action="{{ route('impersonation.leave') }}">
            @csrf
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-1 text-xs font-semibold text-white hover:bg-zinc-800">{{ __('moderation.impersonation.leave') }}</button>
        </form>
    </div>
@endImpersonating
