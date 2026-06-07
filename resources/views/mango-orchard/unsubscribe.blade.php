<x-site-layout :title="'Unsubscribed — Aamar Malda'">
    <section class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-white rounded-2xl border border-stone-200 p-8 sm:p-10 text-center">
            <span class="inline-block w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-3" aria-hidden="true">✓</span>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">You're unsubscribed</h1>
            <p class="mt-3 text-stone-600 text-sm">
                We won't send any more monthly newsletters to <span class="font-medium text-stone-900">{{ $user->email }}</span>.
                Any other notifications you opted into still apply.
            </p>
            <p class="mt-4 text-xs text-stone-500">
                Changed your mind? Sign in and re-subscribe from
                <a href="{{ route('profile.edit') }}" class="underline hover:text-stone-900">your preferences</a>.
            </p>
            <a href="{{ route('home') }}" class="mt-6 inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">Back to the orchard</a>
        </div>
    </section>
</x-site-layout>
