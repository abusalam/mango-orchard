<x-guest-layout title="Your preferences" step="preferences">
    <div class="rounded-2xl bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 shadow-sm p-6 sm:p-10">
        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">A few preferences</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-300">Pick a favorite and decide how we should tap you on the shoulder.</p>

        <form method="POST" action="{{ route('onboarding.preferences') }}" class="mt-8 space-y-6">
            @csrf

            <div>
                <label for="favorite_variety_id" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Favorite variety <span class="text-stone-400 font-normal">(optional)</span></label>
                
        <select name="favorite_variety_id" id="favorite_variety_id"
                        class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400"
    >
                    <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="">No favorite yet</option>
                    @foreach ($varieties as $variety)
                        <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $variety->id }}" @selected((int) old('favorite_variety_id', $user->favorite_variety_id) === $variety->id)>
                            {{ $variety->name }} — {{ $variety->origin }}
                        </option>
                    @endforeach
                </select>
                @error('favorite_variety_id') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-3 pt-2">
                <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-800 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                    <input type="checkbox" name="notify_seasonal" value="1"
                           @checked(old('notify_seasonal', $user->notify_seasonal))
                           class="mt-1 rounded text-orange-500 focus:ring-orange-400">
                    <span>
                        <span class="block text-sm font-medium text-stone-800 dark:text-stone-200">Notify me when a variety hits its season</span>
                        <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">A heads-up when something like Alphonso or Chaunsa comes into peak.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-800 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                    <input type="checkbox" name="subscribe_newsletter" value="1"
                           @checked(old('subscribe_newsletter', $user->subscribe_newsletter))
                           class="mt-1 rounded text-orange-500 focus:ring-orange-400">
                    <span>
                        <span class="block text-sm font-medium text-stone-800 dark:text-stone-200">Send me the monthly orchard newsletter</span>
                        <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">Seasonal stories, new cultivar profiles, and tasting notes. Once a month, never more.</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-stone-100 dark:border-stone-800">
                <a href="{{ route('onboarding.profile') }}" class="text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100">← Back</a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                    Finish setup
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
