<x-admin-layout title="Impersonate" active="impersonate">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Impersonate</h1>
        <p class="mt-1 text-stone-600">Sign in temporarily as another user — either a specific person, or whoever happens to hold a given role.</p>
    </header>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="mb-10">
        <h2 class="text-lg font-medium text-stone-900 mb-3">By role</h2>
        <p class="text-sm text-stone-500 mb-4">Pick a role and we'll log you in as the first user we find who holds it (other than yourself).</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($roles as $role)
                <form method="POST" action="{{ route('admin.impersonate.role', $role) }}" class="bg-white rounded-2xl border border-stone-200 p-4">
                    @csrf
                    <p class="font-semibold text-stone-900">{{ Str::headline($role->name) }}</p>
                    <p class="text-xs text-stone-500 mt-1">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }} · {{ $role->name }}</p>
                    <button type="submit"
                            @disabled($role->users_count === 0)
                            class="mt-3 inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 font-medium text-xs hover:bg-stone-800 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        Impersonate any {{ $role->name }}
                    </button>
                </form>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="text-lg font-medium text-stone-900 mb-3">By user</h2>
        <p class="text-sm text-stone-500 mb-4">First 100 users alphabetically. Use <a href="{{ route('admin.users.index') }}" class="text-orange-700 hover:text-orange-900">the user admin page</a> for the full list.</p>

        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-stone-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-5 py-3 font-medium">Email</th>
                        <th class="px-5 py-3 font-medium">Roles</th>
                        <th class="px-5 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($users as $listedUser)
                        <tr class="odd:bg-stone-50/60 hover:bg-amber-50/60 transition-colors">
                            <td class="px-5 py-3 font-medium text-stone-900">{{ $listedUser->name }}</td>
                            <td class="px-5 py-3 text-stone-600">{{ $listedUser->email }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($listedUser->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-700 border border-stone-200">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-stone-400 italic text-xs">no roles</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($listedUser->id === auth()->id())
                                    <span class="text-xs text-stone-400 italic">that's you</span>
                                @elseif ($listedUser->hasRole(\App\Roles::SUPERUSER) && ! auth()->user()->hasRole(\App\Roles::SUPERUSER))
                                    <span class="text-xs text-stone-400 italic">superuser</span>
                                @else
                                    <form method="POST" action="{{ route('admin.impersonate.user', $listedUser) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 font-medium text-xs hover:bg-stone-800 transition-colors">Impersonate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
