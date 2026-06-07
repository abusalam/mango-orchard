<x-admin-layout title="Newsletter" active="mango-newsletter">
    <header class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Orchard newsletter</h1>
            <p class="mt-1 text-stone-600 text-sm">Compose and send monthly issues to opted-in subscribers.</p>
        </div>
        <div class="flex items-end gap-4">
            <p class="text-sm text-stone-500" data-testid="newsletter-subscriber-count">
                <span class="font-medium text-stone-900">{{ $subscriberCount }}</span> {{ Str::plural('subscriber', $subscriberCount) }}
            </p>
            <a href="{{ route('admin.mango-orchard.newsletter.create') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">New issue</a>
        </div>
    </header>

    {{-- ============== Drafts ============== --}}
    <section class="mb-10" data-testid="newsletter-drafts">
        <h2 class="text-lg font-semibold text-stone-900 mb-3">Drafts</h2>
        <div class="bg-white rounded-2xl border border-stone-200">
            @if ($drafts->isEmpty())
                <p class="px-6 py-12 text-center text-stone-500 text-sm">No drafts in flight.</p>
            @else
                <ul class="divide-y divide-stone-100">
                    @foreach ($drafts as $issue)
                        <li class="px-4 py-3 flex flex-wrap items-center justify-between gap-3" data-testid="newsletter-draft-{{ $issue->id }}">
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900 truncate">{{ $issue->subject }}</p>
                                <p class="text-xs text-stone-500 mt-0.5">
                                    Updated {{ $issue->updated_at->diffForHumans() }}
                                    @if ($issue->author) · by {{ $issue->author->name }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                <a href="{{ route('admin.mango-orchard.newsletter.edit', $issue) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs">Edit</a>
                                <x-confirm-form
                                    :action="route('admin.mango-orchard.newsletter.destroy', $issue)"
                                    method="DELETE"
                                    title="Delete draft?"
                                    body="This draft will be permanently removed. Sent issues can't be deleted."
                                    confirm-label="Delete">
                                    <button type="button"
                                            class="inline-flex items-center px-3 py-1.5 rounded-full bg-rose-50 text-rose-900 border border-rose-200 hover:bg-rose-100 text-xs"
                                            data-testid="delete-draft-{{ $issue->id }}">Delete</button>
                                </x-confirm-form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- ============== Sent ============== --}}
    <section data-testid="newsletter-sent">
        <h2 class="text-lg font-semibold text-stone-900 mb-3">Sent</h2>
        <div class="bg-white rounded-2xl border border-stone-200">
            @if ($sentIssues->isEmpty())
                <p class="px-6 py-12 text-center text-stone-500 text-sm">No issues sent yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Subject</th>
                            <th class="px-4 py-2 font-medium hidden sm:table-cell">Sent</th>
                            <th class="px-4 py-2 font-medium">Recipients</th>
                            <th class="px-4 py-2 font-medium hidden md:table-cell">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($sentIssues as $issue)
                            <tr class="odd:bg-white even:bg-stone-50/50" data-testid="newsletter-sent-{{ $issue->id }}">
                                <td class="px-4 py-3 font-medium text-stone-900">{{ $issue->subject }}</td>
                                <td class="px-4 py-3 text-xs text-stone-500 hidden sm:table-cell">{{ $issue->sent_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-stone-700">{{ $issue->sent_to_count }}</td>
                                <td class="px-4 py-3 text-xs text-stone-500 hidden md:table-cell">{{ $issue->author?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</x-admin-layout>
