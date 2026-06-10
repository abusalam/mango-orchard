<x-admin-layout title="MPCP — New section" active="mpcp">
    <a href="{{ route('admin.mpcp.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 mb-3">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Sections
    </a>

    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">New section</h1>
    </header>

    @include('admin.mpcp.sections._form')
</x-admin-layout>
