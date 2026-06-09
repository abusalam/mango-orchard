@if (! empty($impersonating))
    <div class="bg-rose-700 text-rose-50" data-testid="impersonation-banner">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-sm">
            <p>
                <strong>Acting as</strong>
                <span class="font-medium">{{ $impersonating['target_name'] }}</span>
                <span class="text-rose-200 text-xs">({{ $impersonating['target_email'] }})</span>
                — back as <strong>{{ $impersonating['actor_name'] }}</strong> when done.
                @if (! empty($impersonating['reason_label']))
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full bg-rose-800 text-rose-100 text-[10px] uppercase tracking-wide font-medium">{{ $impersonating['reason_label'] }}</span>
                @endif
            </p>
            <form method="POST" action="{{ route('impersonate.stop') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-3 py-1 rounded-full bg-rose-50 text-rose-900 text-xs font-semibold hover:bg-white dark:bg-stone-950 transition-colors" data-testid="impersonate-stop">
                    Return to my account
                </button>
            </form>
        </div>
    </div>
@endif
