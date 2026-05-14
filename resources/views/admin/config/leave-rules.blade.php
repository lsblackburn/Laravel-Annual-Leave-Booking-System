<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text]  leading-tight">
            {{ __('Modify Leave Rules') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('admin.partials.leave-refresh-date-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('admin.partials.leave-allowance-rules-form')
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
