<x-admin-layout :title="$issue->exists ? 'Edit issue' : 'New issue'" active="mango-newsletter">
    <a href="{{ route('admin.mango-orchard.newsletter.index') }}"
       class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 mb-3"
       data-testid="back-to-newsletter">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        All issues
    </a>

    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $issue->exists ? 'Edit issue' : 'New issue' }}</h1>
        <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">
            Once sent, an issue can't be edited or deleted — kept for the audit trail.
            Currently <strong>{{ $subscriberCount }}</strong> {{ Str::plural('subscriber', $subscriberCount) }} will receive it.
        </p>
    </header>

    @if ($errors->has('send'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">{{ $errors->first('send') }}</div>
    @endif

    <form method="POST"
          action="{{ $issue->exists ? route('admin.mango-orchard.newsletter.update', $issue) : route('admin.mango-orchard.newsletter.store') }}"
          class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 space-y-5">
        @csrf
        @if ($issue->exists) @method('PUT') @endif

        <div>
            <label for="subject" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Subject</label>
            <input id="subject" name="subject" type="text" required maxlength="200"
                   value="{{ old('subject', $issue->subject) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100"
                   data-testid="newsletter-subject">
            @error('subject') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="body" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Body</label>
            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Markdown supported (<code>**bold**</code>, <code>[link](url)</code>). Separate paragraphs with a blank line.</p>
            
        <textarea id="body" name="body" rows="16" required maxlength="20000"
                      class="mt-2 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-sm"
    
                      data-testid="newsletter-body">{{ old('body', $issue->body) }}</textarea>
            @error('body') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-stone-100 dark:border-stone-800">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="save-draft">{{ $issue->exists ? 'Save draft' : 'Create draft' }}</button>
            <a href="{{ route('admin.mango-orchard.newsletter.index') }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100">Cancel</a>
        </div>
    </form>

    {{-- Send action: separate confirm form so the Save and Send paths
         can't accidentally fire together. Visible only on saved drafts. --}}
    @if ($issue->exists)
        <div class="mt-6 bg-amber-50 dark:bg-stone-900 border border-amber-200 dark:border-stone-800 rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-amber-900">Send to subscribers</h2>
            <p class="mt-1 text-sm text-amber-800">Sends the most recently saved version. Save your edits first.</p>
            <div class="mt-4">
                <x-confirm-form
                    :action="route('admin.mango-orchard.newsletter.send', $issue)"
                    method="POST"
                    title="Send newsletter to {{ $subscriberCount }} subscribers?"
                    body="This queues the email to every opted-in subscriber. Once sent, the issue can't be edited."
                    confirm-label="Send now">
                    <button type="button"
                            class="inline-flex items-center px-4 py-2 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 text-sm font-medium"
                            data-testid="send-newsletter">Send to {{ $subscriberCount }} {{ Str::plural('subscriber', $subscriberCount) }}</button>
                </x-confirm-form>
            </div>
        </div>
    @endif
</x-admin-layout>
