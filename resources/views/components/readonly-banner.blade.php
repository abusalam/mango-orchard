@if (! empty($readonlyModeEnabled))
    <div class="bg-amber-400 text-amber-950 border-b border-amber-500" data-testid="readonly-banner">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5 9V7a5 5 0 1 1 10 0v2h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h1zm2 0V7a3 3 0 1 1 6 0v2H7z"/>
            </svg>
            <p>
                <strong>Read-only mode is on.</strong>
                Browsing and sign-in still work, but creating, editing, and deleting are paused.
            </p>
        </div>
    </div>
@endif
