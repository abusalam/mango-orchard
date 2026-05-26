<x-site-layout :title="'Advisories — Admin'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Orchard advisories</h1>
                <p class="mt-2 text-stone-600">Manage seasonal alerts, best-practice notes, and pest warnings. Drafts and expired advisories are listed here too — only published+active ones reach the public page.</p>
            </div>
            <a href="{{ route('admin.advisories.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                New advisory
            </a>
        </div>

        @if ($advisories->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center">
                <p class="text-stone-600">No advisories yet.</p>
                <a href="{{ route('admin.advisories.create') }}" class="mt-4 inline-block text-orange-700 font-medium">Issue the first one →</a>
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-50 text-stone-600 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Issued</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Severity</th>
                            <th class="px-4 py-3 text-left">Targets</th>
                            <th class="px-4 py-3 text-left">State</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($advisories as $advisory)
                            <tr class="hover:bg-stone-50" data-testid="admin-advisory-row">
                                <td class="px-4 py-3 whitespace-nowrap text-stone-700">{{ $advisory->issued_at?->toFormattedDateString() ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.advisories.edit', $advisory) }}" class="font-medium text-stone-900 hover:text-orange-700">{{ $advisory->title }}</a>
                                    @if ($advisory->issuer)
                                        <div class="text-xs text-stone-500">by {{ $advisory->issuer->name }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-stone-700">{{ \App\Models\Advisory::CATEGORIES[$advisory->category] ?? $advisory->category }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border',
                                        'bg-rose-200 text-rose-900 border-rose-300' => $advisory->severity === \App\Models\Advisory::SEVERITY_URGENT,
                                        'bg-amber-200 text-amber-900 border-amber-300' => $advisory->severity === \App\Models\Advisory::SEVERITY_WARNING,
                                        'bg-stone-100 text-stone-700 border-stone-200' => $advisory->severity === \App\Models\Advisory::SEVERITY_INFO,
                                    ])>{{ \App\Models\Advisory::SEVERITIES[$advisory->severity] }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-700">
                                    @if ($advisory->isGeneral())
                                        <em class="text-stone-400">All</em>
                                    @else
                                        {{ $advisory->varieties->pluck('name')->take(3)->join(', ') }}{{ $advisory->varieties->count() > 3 ? ', +'.($advisory->varieties->count() - 3) : '' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (! $advisory->published)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-700 border border-stone-200">Draft</span>
                                    @elseif ($advisory->isExpired())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-200 text-stone-700 border border-stone-300">Expired</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 text-emerald-900 border border-emerald-200">Published</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.advisories.edit', $advisory) }}" class="text-orange-700 hover:text-orange-900 font-medium text-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $advisories->links() }}</div>
        @endif
    </section>
</x-site-layout>
