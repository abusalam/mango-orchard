<x-guest-layout title="Tell us about you" step="profile">
    <div class="rounded-2xl bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 shadow-sm p-6 sm:p-10">
        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">Hi {{ $user->name }}, tell us a bit about you</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-300">A couple of quick questions so we can tailor your orchard.</p>

        <form method="POST" action="{{ route('onboarding.profile') }}" class="mt-8 space-y-6">
            @csrf

            <div>
                <label for="region" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Where are you mango-watching from?</label>
                <input type="text" name="region" id="region" required maxlength="120"
                       value="{{ old('region', $user->region) }}"
                       placeholder="e.g. Mumbai, India"
                       class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @error('region') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="block text-sm font-medium text-stone-800 dark:text-stone-200">How would you describe yourself?</legend>
                <div class="mt-3 grid sm:grid-cols-2 gap-3">
                    @foreach ($expertiseLevels as $key => $label)
                        <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-800 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                            <input type="radio" name="expertise" value="{{ $key }}" required
                                   @checked(old('expertise', $user->expertise) === $key)
                                   class="mt-1 text-orange-500 focus:ring-orange-400">
                            <span class="text-sm font-medium text-stone-800 dark:text-stone-200">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('expertise') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </fieldset>

            <div class="flex items-center justify-end pt-4 border-t border-stone-100 dark:border-stone-800">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                    Continue
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M11 6l4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
