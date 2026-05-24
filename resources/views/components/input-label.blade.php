@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-stone-800']) }}>
    {{ $value ?? $slot }}
</label>
