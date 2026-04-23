@props([
    'disabled' => false,
    'value' => null,
])

<input
    @disabled($disabled)
    value="{{ old($attributes->get('name'), $value) }}"
    {{ $attributes->merge([
        'class' => 'bg-[--color-background] border-[--color-border] text-[--color-text] placeholder:text-[--color-surface-alt] focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm'
    ]) }}
>