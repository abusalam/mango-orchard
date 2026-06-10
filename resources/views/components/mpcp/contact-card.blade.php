@props([
    'name' => null,
    'designation' => null,
    'organization' => null,
    'address' => null,
    'phone' => null,
    'email' => null,
    'note' => null,
    'roleLabel' => null,   // e.g. "NODAL OFFICER" — renders as a top-right pill
])

@php
    // Phone field may carry " · " separated values (phone + telefax). Split for display.
    $phoneParts = $phone ? array_filter(array_map('trim', explode('·', $phone))) : [];
    // Email field may carry multiple addresses separated by " · ".
    $emailParts = $email ? array_filter(array_map('trim', explode('·', $email))) : [];
@endphp

<article class="relative bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl border-t-4 border-t-emerald-600 dark:border-t-emerald-700 shadow-sm hover:shadow-md hover:border-amber-400 dark:hover:border-amber-700 transition-all p-5 sm:p-6"
         data-testid="mpcp-contact-card">

    @if ($roleLabel)
        <span class="absolute -top-3 right-4 inline-flex items-center px-3 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-800">
            {{ $roleLabel }}
        </span>
    @endif

    @if ($name)
        <h3 class="text-base sm:text-lg font-semibold text-stone-900 dark:text-stone-100 leading-snug">{{ $name }}</h3>
    @endif

    @if ($designation)
        <p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ $designation }}</p>
    @endif

    @if ($organization)
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400 whitespace-pre-line">{{ $organization }}</p>
    @endif

    @if ($address || $phone || $email)
        <dl class="mt-4 space-y-2 text-sm">
            @if ($address)
                <div class="flex gap-2">
                    <dt class="shrink-0 w-16 text-stone-500 dark:text-stone-400">Address</dt>
                    <dd class="text-stone-800 dark:text-stone-200">{{ $address }}</dd>
                </div>
            @endif

            @if (! empty($phoneParts))
                <div class="flex gap-2">
                    <dt class="shrink-0 w-16 text-stone-500 dark:text-stone-400">Phone</dt>
                    <dd class="text-orange-700 dark:text-amber-400 font-medium">
                        @foreach ($phoneParts as $idx => $p)
                            <a href="tel:{{ preg_replace('/[^+0-9]/', '', $p) }}" class="hover:underline">{{ $p }}</a>@if ($idx < count($phoneParts) - 1)<span class="text-stone-400"> · </span>@endif
                        @endforeach
                    </dd>
                </div>
            @endif

            @if (! empty($emailParts))
                <div class="flex gap-2">
                    <dt class="shrink-0 w-16 text-stone-500 dark:text-stone-400">Email</dt>
                    <dd class="text-orange-700 dark:text-amber-400 font-medium min-w-0 break-all">
                        @foreach ($emailParts as $idx => $e)
                            <a href="mailto:{{ $e }}" class="hover:underline">{{ $e }}</a>@if ($idx < count($emailParts) - 1)<span class="text-stone-400"> · </span>@endif
                        @endforeach
                    </dd>
                </div>
            @endif
        </dl>
    @endif

    @if ($note)
        <p class="mt-4 pt-3 border-t border-stone-100 dark:border-stone-800 text-xs italic text-stone-500 dark:text-stone-400">{{ $note }}</p>
    @endif
</article>
