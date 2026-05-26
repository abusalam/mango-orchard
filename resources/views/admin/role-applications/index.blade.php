<x-admin-layout title="Role applications" active="role-applications">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Role applications</h1>
        <p class="mt-1 text-stone-600">Users requesting elevated roles. Approving assigns the role immediately.</p>
    </header>

    <section class="mb-10">
        <h2 class="text-lg font-medium text-stone-900 mb-3">Pending <span class="text-stone-400 text-sm">({{ $pending->count() }})</span></h2>

        @if ($pending->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-10 text-center text-stone-500" data-testid="role-applications-empty">
                Nothing waiting for review.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($pending as $application)
                    <article class="bg-white rounded-2xl border border-stone-200 p-5 sm:p-6" data-testid="pending-application">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div>
                                <p class="text-base font-semibold text-stone-900">{{ $application->user->name }}
                                    <span class="text-xs font-normal text-stone-500">{{ $application->user->email }}</span>
                                </p>
                                <p class="mt-1 text-sm text-stone-700">
                                    Requesting role:
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-stone-100 text-stone-800 text-xs font-medium border border-stone-200">{{ $application->role->name }}</span>
                                </p>
                                @if ($application->message)
                                    <blockquote class="mt-3 text-sm text-stone-600 border-l-2 border-stone-200 pl-3 italic">{{ $application->message }}</blockquote>
                                @endif
                                <p class="mt-2 text-xs text-stone-400">Submitted {{ $application->created_at->diffForHumans() }}</p>
                            </div>

                            <div class="flex gap-2 shrink-0">
                                <form method="POST" action="{{ route('admin.role-applications.approve', $application) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-700 text-amber-50 font-medium hover:bg-emerald-800 transition-colors text-xs">Approve</button>
                                </form>
                                @php($rejectTitle = "Reject {$application->user->name}'s application?")
                                @php($rejectMessage = "They will lose their pending request for the {$application->role->name} role. They can re-apply later.")
                                <x-confirm-form
                                    :action="route('admin.role-applications.reject', $application)"
                                    method="POST"
                                    :title="$rejectTitle"
                                    :message="$rejectMessage"
                                    confirm-label="Reject application"
                                >
                                    <button type="button" class="inline-flex items-center px-4 py-2 rounded-full border border-rose-300 text-rose-700 font-medium hover:bg-rose-50 transition-colors text-xs">Reject</button>
                                </x-confirm-form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-medium text-stone-900 mb-3">Recently reviewed</h2>

        @if ($reviewed->isEmpty())
            <p class="text-sm text-stone-500">No applications have been reviewed yet.</p>
        @else
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left">
                        <tr>
                            <th class="px-5 py-3 font-medium">When</th>
                            <th class="px-5 py-3 font-medium">Applicant</th>
                            <th class="px-5 py-3 font-medium">Role</th>
                            <th class="px-5 py-3 font-medium">Decision</th>
                            <th class="px-5 py-3 font-medium">Reviewer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($reviewed as $application)
                            <tr data-testid="reviewed-application">
                                <td class="px-5 py-3 text-stone-600 whitespace-nowrap" title="{{ $application->reviewed_at }}">
                                    {{ $application->reviewed_at?->diffForHumans() }}
                                </td>
                                <td class="px-5 py-3 text-stone-700">
                                    {{ $application->user->name }}
                                    <span class="block text-xs text-stone-400">{{ $application->user->email }}</span>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-stone-800">{{ $application->role->name }}</td>
                                <td class="px-5 py-3">
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border',
                                        'bg-emerald-100 text-emerald-900 border-emerald-200' => $application->status === \App\Models\RoleApplication::STATUS_APPROVED,
                                        'bg-rose-100 text-rose-900 border-rose-200' => $application->status === \App\Models\RoleApplication::STATUS_REJECTED,
                                    ])>{{ Str::headline($application->status) }}</span>
                                </td>
                                <td class="px-5 py-3 text-stone-700">
                                    {{ $application->reviewer?->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-admin-layout>
