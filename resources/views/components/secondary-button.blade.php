<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2 bg-white border border-stone-300 rounded-full font-medium text-sm text-stone-700 hover:bg-stone-50 hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
