@php
    $type = session('success') ? 'success' : (session('error') ? 'error' : null);
    $message = session($type);
@endphp

@if ($type)
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div {{ $attributes->merge([
            'class' => ($type === 'success'
                ? 'bg-[--color-success] text-black'
                : 'bg-[--color-danger] text-white'
            ) . ' rounded-xl px-8 py-4 border-box'
        ]) }}>
            {{ $message }}
        </div>
    </div>
@endif