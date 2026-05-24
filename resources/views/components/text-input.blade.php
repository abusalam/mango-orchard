@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-stone-300 focus:border-orange-400 focus:ring-orange-400 rounded-lg shadow-sm']) }}>
