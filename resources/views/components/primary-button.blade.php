<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2 bg-stone-900 border border-transparent rounded-full font-medium text-sm text-amber-50 hover:bg-stone-800 focus:bg-stone-800 active:bg-black focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-amber-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
