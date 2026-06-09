<section data-testid="profile-delegations">
    <header>
        <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100">
            {{ __('Role delegations') }}
        </h2>

        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
            {{ __('Grant a role you already hold to another orchard member, or take it back later.') }}
        </p>
    </header>

    {{-- ── Delegate a role you hold ───────────────────────────────────── --}}
    @if ($delegatableRolesHeld->isEmpty())
        <p class="mt-6 text-sm text-stone-500 dark:text-stone-400">
            {{ __('You don\'t hold any delegatable roles right now. Roles like grower, curator and convener can be passed along once you have them.') }}
        </p>
    @else
        <div class="mt-6 space-y-4">
            @foreach ($delegatableRolesHeld as $role)
                <div class="rounded-xl border border-stone-200 dark:border-stone-800 p-4 sm:p-5">
                    <p class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ Str::headline($role->name) }} <span class="text-stone-400 font-normal">({{ $role->name }})</span></p>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ __('Delegate this role to another user by email. They\'ll get it immediately.') }}</p>

                    <form method="POST" action="{{ route('role-delegations.store') }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role->id }}">
                        <div class="flex-1">
                            <input type="email" name="recipient_email" required maxlength="255"
                                   placeholder="recipient@example.com"
                                   value="{{ old('role_id') == $role->id ? old('recipient_email') : '' }}"
                                   class="block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400 text-sm"
                                   data-testid="delegate-email-input">
                            @if (old('role_id') == $role->id)
                                <x-input-error class="mt-2" :messages="$errors->get('recipient_email')" />
                                <x-input-error class="mt-2" :messages="$errors->get('role_id')" />
                            @endif
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-xs whitespace-nowrap">
                            {{ __('Delegate role') }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Delegations you've granted (with revoke) ──────────────────── --}}
    @if ($delegationsGranted->isNotEmpty())
        <div class="mt-8">
            <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400 mb-2">{{ __('You\'ve delegated to') }}</h3>
            <ul class="divide-y divide-stone-100 dark:divide-stone-800 text-sm rounded-xl border border-stone-200 dark:border-stone-800 overflow-hidden bg-white dark:bg-stone-950" data-testid="delegations-granted">
                @foreach ($delegationsGranted as $delegation)
                    <li class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-stone-800 dark:text-stone-200"><strong>{{ $delegation->recipient->name }}</strong>
                                <span class="text-stone-500 dark:text-stone-400">{{ $delegation->recipient->email }}</span></p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                {{ __('Role') }}: <span class="font-mono">{{ $delegation->role?->name }}</span>
                                · {{ __('Granted') }} {{ $delegation->delegated_at->diffForHumans() }}
                            </p>
                        </div>
                        <x-confirm-form
                            :action="route('role-delegations.destroy', $delegation)"
                            method="DELETE"
                            title="Revoke this delegation?"
                            :message="$delegation->recipient->name.' will lose the '.$delegation->role?->name.' role unless they were granted it independently.'"
                            confirm-label="Revoke"
                        >
                            <button type="button" class="text-xs text-rose-700 dark:text-rose-400 hover:text-rose-900 font-medium" data-testid="revoke-delegation-granted">{{ __('Revoke') }}</button>
                        </x-confirm-form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Delegations granted to you (with self-renounce) ───────────── --}}
    @if ($delegationsReceived->isNotEmpty())
        <div class="mt-8">
            <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400 mb-2">{{ __('Delegated to you') }}</h3>
            <ul class="divide-y divide-stone-100 dark:divide-stone-800 text-sm rounded-xl border border-stone-200 dark:border-stone-800 overflow-hidden bg-white dark:bg-stone-950" data-testid="delegations-received">
                @foreach ($delegationsReceived as $delegation)
                    <li class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-stone-800 dark:text-stone-200"><span class="font-mono">{{ $delegation->role?->name }}</span></p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                {{ __('Granted by') }} <strong class="text-stone-700 dark:text-stone-300">{{ $delegation->delegator?->name ?? __('a removed user') }}</strong>
                                · {{ $delegation->delegated_at->diffForHumans() }}
                            </p>
                        </div>
                        <x-confirm-form
                            :action="route('role-delegations.destroy', $delegation)"
                            method="DELETE"
                            title="Give this role back?"
                            :message="'You\'ll lose the '.$delegation->role?->name.' role. The delegator can re-grant it later.'"
                            confirm-label="Renounce role"
                            variant="warning"
                        >
                            <button type="button" class="text-xs text-rose-700 dark:text-rose-400 hover:text-rose-900 font-medium" data-testid="revoke-delegation-received">{{ __('Renounce') }}</button>
                        </x-confirm-form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
