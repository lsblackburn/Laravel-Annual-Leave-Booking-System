@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-1 pt-1 border-b-4 border-[--color-primary] text-sm font-medium leading-5 text-[--color-text] focus:outline-none focus:border-[--color-primary] transition duration-150 ease-in-out'
    : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[--color-text] hover:text-[--color-subtletext] hover:border-[--color-primary] focus:outline-none focus:text-[--color-text] focus:border-[--color-primary] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
