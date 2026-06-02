<x-admin-layout title="Role delegations" active="role-delegations">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Role delegations</h1>
        <p class="mt-1 text-stone-600">Peer-to-peer role grants — created by users who hold a delegatable role. You can revoke any of them on behalf of either party.</p>
    </header>

    <section class="mb-10">
        <h2 class="text-lg font-medium text-stone-900 mb-3">Active <span class="text-stone-400 text-sm">({{ $active->count() }})</span></h2>

        @if ($active->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-10 text-center text-stone-500" data-testid="delegations-active-empty">
                No active delegations right now.
            </div>
        @else
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left">
                        <tr>
                            <th class="px-5 py-3 font-medium">When</th>
                            <th class="px-5 py-3 font-medium">Recipient</th>
                            <th class="px-5 py-3 font-medium">Role</th>
                            <th class="px-5 py-3 font-medium">Delegator</th>
                            <th class="px-5 py-3 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($active as $delegation)
                            <tr class="odd:bg-stone-50/60 hover:bg-amber-50/60 transition-colors" data-testid="active-delegation-row">
                                <td class="px-5 py-3 text-stone-600 whitespace-nowrap" title="{{ $delegation->delegated_at }}">
                                    {{ $delegation->delegated_at->diffForHumans() }}
                                </td>
                                <td class="px-5 py-3 text-stone-700">
                                    {{ $delegation->recipient->name }}
                                    <span class="block text-xs text-stone-400">{{ $delegation->recipient->email }}</span>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-stone-800">{{ $delegation->role?->name }}</td>
                                <td class="px-5 py-3 text-stone-700">
                                    {{ $delegation->delegator?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <x-confirm-form
                                        :action="route('role-delegations.destroy', $delegation)"
                                        method="DELETE"
                                        title="Revoke this delegation?"
                                        :message="$delegation->recipient->name.' will lose the '.$delegation->role?->name.' role unless they were granted it independently.'"
                                        confirm-label="Revoke delegation"
                                    >
                                        <button type="button" class="text-rose-700 hover:text-rose-900 font-medium" data-testid="admin-revoke-delegation">Revoke</button>
                                    </x-confirm-form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-medium text-stone-900 mb-3">Recently revoked</h2>

        @if ($revoked->isEmpty())
            <p class="text-sm text-stone-500">No delegations have been revoked yet.</p>
        @else
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left">
                        <tr>
                            <th class="px-5 py-3 font-medium">When revoked</th>
                            <th class="px-5 py-3 font-medium">Recipient</th>
                            <th class="px-5 py-3 font-medium">Role</th>
                            <th class="px-5 py-3 font-medium">Granted by</th>
                            <th class="px-5 py-3 font-medium">Revoked by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($revoked as $delegation)
                            <tr class="odd:bg-stone-50/60 hover:bg-amber-50/60 transition-colors">
                                <td class="px-5 py-3 text-stone-600 whitespace-nowrap" title="{{ $delegation->revoked_at }}">
                                    {{ $delegation->revoked_at?->diffForHumans() }}
                                </td>
                                <td class="px-5 py-3 text-stone-700">
                                    {{ $delegation->recipient->name }}
                                    <span class="block text-xs text-stone-400">{{ $delegation->recipient->email }}</span>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-stone-800">{{ $delegation->role?->name }}</td>
                                <td class="px-5 py-3 text-stone-700">{{ $delegation->delegator?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-stone-700">{{ $delegation->revoker?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-admin-layout>
