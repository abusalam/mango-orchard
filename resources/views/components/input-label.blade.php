@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-stone-800 dark:text-stone-200']) }}>
    {{ $value ?? $slot }}
</label>
