@php
    $type = session('success') ? 'success' : (session('error') ? 'error' : null);
    $message = session($type);
@endphp

@if ($type)
    <div x-data="{ destroy: false }" x-show="!destroy" class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div {{ $attributes->merge([
            'class' => ($type === 'success'
                ? 'bg-[--color-success] text-black'
                : 'bg-[--color-danger] text-white'
            ) . ' rounded-xl px-8 py-4 border-box flex flex-row justify-between items-center gap-4'
        ]) }}>
            {{ $message }}

            <button @click="destroy = true" class="ml-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>

        </div>

    </div>
@endif