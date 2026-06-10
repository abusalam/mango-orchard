<x-admin-layout :title="'MPCP — '.$section->title_en" active="mpcp">
    <a href="{{ route('admin.mpcp.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 mb-3">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Sections
    </a>

    <header class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Edit section</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">{{ $section->title_en }} @if ($section->title_bn) · {{ $section->title_bn }} @endif</p>
        </div>
        <a href="{{ route('admin.mpcp.entries.index', $section) }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">Manage entries →</a>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100">{{ session('status') }}</div>
    @endif

    @include('admin.mpcp.sections._form')
</x-admin-layout>
