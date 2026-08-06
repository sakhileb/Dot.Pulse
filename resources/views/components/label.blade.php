@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[var(--mist)]']) }}>
    {{ $value ?? $slot }}
</label>
