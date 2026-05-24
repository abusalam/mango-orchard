<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-stone-900 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <p class="text-sm text-stone-500">Welcome back to the orchard.</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-2xl p-4 text-sm" data-testid="flash-status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden">
                <div class="p-6 text-stone-700">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
