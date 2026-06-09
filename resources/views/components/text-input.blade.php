@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white dark:bg-stone-800 border-stone-300 dark:border-stone-700 text-stone-900 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:border-orange-400 dark:focus:border-amber-400 focus:ring-orange-400 dark:focus:ring-amber-400 rounded-lg shadow-sm']) }}>
