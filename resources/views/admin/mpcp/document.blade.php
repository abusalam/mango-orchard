<x-admin-layout title="MPCP — Document" active="mpcp">
    <a href="{{ route('admin.mpcp.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 mb-3">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Sections
    </a>

    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Document chrome</h1>
        <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Title, attribution blockquote, "About" intro, and "Prepared by" footer that wrap the seven sections on /mpcp.</p>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.mpcp.document.update') }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 space-y-5" data-testid="mpcp-document-form">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="title_en" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Title (English)</label>
                <input id="title_en" name="title_en" type="text" required value="{{ old('title_en', $document->title_en) }}"
                       class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
                @error('title_en') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="title_bn" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Title (Bengali)</label>
                <input id="title_bn" name="title_bn" type="text" value="{{ old('title_bn', $document->title_bn) }}"
                       class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
            </div>
        </div>

        <div>
            <label for="website_url" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Website URL</label>
            <input id="website_url" name="website_url" type="url" value="{{ old('website_url', $document->website_url) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        </div>

        @foreach ([
            'attribution_md_en' => 'Attribution blockquote (EN, markdown)',
            'attribution_md_bn' => 'Attribution blockquote (BN, markdown)',
            'about_md_en' => '"About this Plan" intro (EN, markdown)',
            'about_md_bn' => '"About this Plan" intro (BN, markdown)',
            'footer_md_en' => '"Prepared by" footer (EN, markdown)',
            'footer_md_bn' => '"Prepared by" footer (BN, markdown)',
        ] as $field => $label)
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ $label }}</label>
                <textarea id="{{ $field }}" name="{{ $field }}" rows="5"
                          class="mt-2 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-sm">{{ old($field, $document->$field) }}</textarea>
                @error($field) <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
        @endforeach

        <div class="flex items-center gap-3 pt-2 border-t border-stone-100 dark:border-stone-800">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="save-document">Save document</button>
            <a href="{{ route('admin.mpcp.index') }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
        </div>
    </form>
</x-admin-layout>
