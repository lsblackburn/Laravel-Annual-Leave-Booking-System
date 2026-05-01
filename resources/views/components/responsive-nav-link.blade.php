@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[--color-primary] text-start text-base font-medium text-[--color-text] bg-[--color-surface-alt] focus:outline-none focus:text-[--color-text] focus:bg-[--color-card] focus:border-[--color-primary] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[--color-text] hover:text-[--color-text] hover:bg-[--color-surface-alt]  hover:border-[--color-border] focus:outline-none focus:text-[--color-text] focus:bg-[--color-surface-alt] focus:border-[--color-border] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
