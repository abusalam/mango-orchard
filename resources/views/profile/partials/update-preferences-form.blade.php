<section>
    <header>
        <h2 class="text-lg font-medium text-stone-900">
            {{ __('Orchard preferences') }}
        </h2>

        <p class="mt-1 text-sm text-stone-600">
            {{ __('The answers you gave during onboarding — update them any time.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.preferences.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="preferences_region" :value="__('Region')" />
            <x-text-input id="preferences_region" name="region" type="text" class="mt-1 block w-full"
                          :value="old('region', $user->region)" required maxlength="120"
                          placeholder="e.g. Mumbai, India" />
            <x-input-error class="mt-2" :messages="$errors->get('region')" />
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-stone-700">{{ __('How would you describe yourself?') }}</legend>
            <div class="mt-3 grid sm:grid-cols-2 gap-3">
                @foreach ($expertiseLevels as $key => $label)
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                        <input type="radio" name="expertise" value="{{ $key }}" required
                               @checked(old('expertise', $user->expertise) === $key)
                               class="mt-1 text-orange-500 focus:ring-orange-400">
                        <span class="text-sm font-medium text-stone-800">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('expertise')" />
        </fieldset>

        <div>
            <x-input-label for="preferences_favorite_variety_id" :value="__('Favorite variety')" />
            <select name="favorite_variety_id" id="preferences_favorite_variety_id"
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                <option value="">{{ __('No favorite yet') }}</option>
                @foreach ($varieties as $variety)
                    <option value="{{ $variety->id }}" @selected((int) old('favorite_variety_id', $user->favorite_variety_id) === $variety->id)>
                        {{ $variety->name }} — {{ $variety->origin }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('favorite_variety_id')" />
        </div>

        <div class="space-y-3">
            <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                <input type="checkbox" name="notify_seasonal" value="1"
                       @checked(old('notify_seasonal', $user->notify_seasonal))
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400">
                <span>
                    <span class="block text-sm font-medium text-stone-800">{{ __('Notify me when a variety hits its season') }}</span>
                    <span class="block text-xs text-stone-500 mt-0.5">{{ __('A heads-up when something like Alphonso or Chaunsa comes into peak.') }}</span>
                </span>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                <input type="checkbox" name="subscribe_newsletter" value="1"
                       @checked(old('subscribe_newsletter', $user->subscribe_newsletter))
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400">
                <span>
                    <span class="block text-sm font-medium text-stone-800">{{ __('Send me the monthly orchard newsletter') }}</span>
                    <span class="block text-xs text-stone-500 mt-0.5">{{ __('Seasonal stories, new cultivar profiles, and tasting notes. Once a month, never more.') }}</span>
                </span>
            </label>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save preferences') }}</x-primary-button>

            @if (session('status') === 'preferences-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-stone-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
