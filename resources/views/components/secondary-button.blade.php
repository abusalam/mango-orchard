<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2 bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-700 rounded-full font-medium text-sm text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-900 hover:border-stone-400 dark:hover:border-stone-600 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 dark:focus:ring-offset-stone-950 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
