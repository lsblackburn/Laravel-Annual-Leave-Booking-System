@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'bg-[--color-background] border-[--color-border] text-[--color-text] placeholder:text-[--color-surface-alt] focus:border-[--color-primary] focus:ring-[--color-primary] rounded-md shadow-sm']) }}>{!! old('{$attributes->get('name')}') !!}</textarea>
