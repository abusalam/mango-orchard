<x-site-layout :title="$scheme->name.' — Aamar Malda'">
    <section class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8">
        <a
            href="{{ route('monitoring.schemes.index') }}"
            class="inline-flex items-center gap-1 text-sm text-stone-600 hover:text-stone-900 mb-3"
            data-testid="back-to-schemes-index"
        >
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            All schemes
        </a>
        <h1 class="text-3xl font-semibold tracking-tight mb-6">Edit scheme</h1>
        {{-- One visual card around both the edit form AND the attachments
             panel. They stay as separate HTML <form>s (HTML5 doesn't allow
             nested forms) but share the bg / border / rounded-2xl so the
             attachments don't read as a stray block below the edit form. --}}
        <div class="bg-white rounded-2xl border border-stone-200">
            <form method="POST" action="{{ route('monitoring.schemes.update', $scheme) }}" class="p-6">
                @method('PUT')
                @include('scheme-monitoring::schemes._form')
            </form>
            <div class="border-t border-stone-100 p-6">
                @include('scheme-monitoring::_attachments', [
                    'attachable' => $scheme,
                    'uploadRoute' => route('monitoring.schemes.attachments.store', $scheme),
                ])
            </div>
        </div>

        <form method="POST" action="{{ route('monitoring.schemes.destroy', $scheme) }}" class="mt-6">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-rose-700 hover:underline">Delete scheme</button>
        </form>
    </section>
</x-site-layout>
