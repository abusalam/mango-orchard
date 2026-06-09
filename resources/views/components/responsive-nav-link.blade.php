@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-orange-400 text-start text-base font-medium text-orange-800 bg-orange-50 focus:outline-none focus:text-orange-900 focus:bg-orange-100 focus:border-orange-600 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-amber-50 dark:hover:bg-stone-800 hover:border-amber-300 focus:outline-none focus:text-stone-900 dark:focus:text-stone-100 focus:bg-amber-50 dark:focus:bg-stone-800 focus:border-amber-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
