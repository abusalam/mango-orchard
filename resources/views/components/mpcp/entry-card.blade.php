@props([
    'columns',   // array of {key, label_en, label_bn, type}
    'data',      // {col_key: value}
    'position',  // entry's 1-based position in its section
])

@php
    // First non-empty column value → title.
    // Remaining columns → labelled rows under it.
    $titleCol = null;
    $titleValue = '';
    foreach ($columns as $col) {
        $v = trim((string) ($data[$col['key']] ?? ''));
        if ($v !== '') {
            $titleCol = $col;
            $titleValue = $v;
            break;
        }
    }
    $bodyCols = array_filter($columns, fn ($c) => $titleCol === null || $c['key'] !== $titleCol['key']);
@endphp

<article class="relative bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl border-t-4 border-t-emerald-500 dark:border-t-emerald-700 shadow-sm hover:shadow-md hover:border-amber-400 dark:hover:border-amber-700 transition-all p-4 sm:p-5"
         data-testid="mpcp-entry-card">

    <span class="absolute top-2 right-3 text-[10px] font-mono text-stone-400 dark:text-stone-500">#{{ $position }}</span>

    <h3 class="text-base font-semibold text-stone-900 dark:text-stone-100 leading-snug pr-8">{{ $titleValue !== '' ? $titleValue : '—' }}</h3>

    @if (! empty($bodyCols))
        <dl class="mt-3 space-y-1.5 text-sm">
            @foreach ($bodyCols as $col)
                @php
                    $value = trim((string) ($data[$col['key']] ?? ''));
                @endphp
                @if ($value !== '')
                    <div class="flex gap-2">
                        <dt class="shrink-0 w-20 text-xs text-stone-500 dark:text-stone-400 pt-0.5">{{ $col['label_en'] }}</dt>
                        <dd class="min-w-0 flex-1
                            @if (in_array($col['type'], ['tel', 'email'])) text-orange-700 dark:text-amber-400 font-medium @else text-stone-800 dark:text-stone-200 @endif">
                            @if ($col['type'] === 'email')
                                <a href="mailto:{{ $value }}" class="hover:underline break-all">{{ $value }}</a>
                            @elseif ($col['type'] === 'tel')
                                <a href="tel:{{ preg_replace('/[^+0-9]/', '', $value) }}" class="hover:underline">{{ $value }}</a>
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endif
            @endforeach
        </dl>
    @endif
</article>
