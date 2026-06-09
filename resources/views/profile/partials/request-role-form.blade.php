<section data-testid="profile-role-applications">
    <header>
        <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100">
            {{ __('Request a role') }}
        </h2>

        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
            {{ __('Need access to something? Apply for a role and an admin will review your request.') }}
        </p>
    </header>

    @if ($applicableRoles->isEmpty())
        <p class="mt-6 text-sm text-stone-500 dark:text-stone-400">
            {{ __('You already hold every role that can be self-applied for. Nothing to request right now.') }}
        </p>
    @else
        <div class="mt-6 space-y-4">
            @foreach ($applicableRoles as $role)
                @php($apps = $roleApplicationsByRoleId->get($role->id, collect()))
                @php($pending = $apps->firstWhere('status', $roleApplicationStatuses['pending']))
                @php($lastReviewed = $apps->whereIn('status', [$roleApplicationStatuses['approved'], $roleApplicationStatuses['rejected']])->first())

                <div class="rounded-xl border border-stone-200 dark:border-stone-800 p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ Str::headline($role->name) }} <span class="text-stone-400 font-normal">({{ $role->name }})</span></p>
                            @if ($role->permissions->isNotEmpty())
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ __('Grants:') }} {{ $role->permissions->pluck('name')->implode(', ') }}</p>
                            @else
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ __('No specific permissions attached — informational role.') }}</p>
                            @endif
                        </div>

                        @if ($pending)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-100 text-amber-900 border border-amber-200 dark:border-stone-800 whitespace-nowrap">{{ __('Pending review') }}</span>
                        @endif
                    </div>

                    @if ($lastReviewed && ! $pending)
                        <p class="mt-3 text-xs text-stone-500 dark:text-stone-400">
                            {{ __('Last reviewed:') }}
                            <span @class([
                                'font-medium',
                                'text-emerald-700' => $lastReviewed->status === $roleApplicationStatuses['approved'],
                                'text-rose-700 dark:text-rose-400' => $lastReviewed->status === $roleApplicationStatuses['rejected'],
                            ])>{{ Str::headline($lastReviewed->status) }}</span>
                            {{ __('on') }} {{ $lastReviewed->reviewed_at?->toFormattedDateString() }}
                            @if ($lastReviewed->decision_note)
                                — <em>{{ $lastReviewed->decision_note }}</em>
                            @endif
                        </p>
                    @endif

                    @if ($pending)
                        <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                {{ __('Submitted') }} {{ $pending->created_at->diffForHumans() }}
                                @if ($pending->message)
                                    — <em>{{ Str::limit($pending->message, 140) }}</em>
                                @endif
                            </p>
                            <x-confirm-form
                                :action="route('role-applications.destroy', $pending)"
                                method="DELETE"
                                title="Cancel this role application?"
                                :message="'Your request for the '.$role->name.' role will be removed. You can re-apply later.'"
                                confirm-label="Cancel application"
                                cancel-label="Keep it"
                                variant="warning"
                            >
                                <button type="button" class="text-xs text-rose-700 dark:text-rose-400 hover:text-rose-900 font-medium">{{ __('Cancel application') }}</button>
                            </x-confirm-form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('role-applications.store') }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="role_id" value="{{ $role->id }}">

                            <div>
                                <label for="message_{{ $role->id }}" class="block text-xs font-medium text-stone-700 dark:text-stone-300">{{ __('Why do you need this role?') }} <span class="text-stone-400 font-normal">({{ __('optional') }})</span></label>
                                <textarea id="message_{{ $role->id }}" name="message" rows="2" maxlength="1000"
                                          class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400 text-sm"
                                          placeholder="{{ __('A line or two of context for the reviewer.') }}">{{ old('role_id') == $role->id ? old('message') : '' }}</textarea>
                                @if (old('role_id') == $role->id)
                                    <x-input-error class="mt-2" :messages="$errors->get('message')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('role_id')" />
                                @endif
                            </div>

                            <div>
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-xs">{{ __('Apply for this role') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
