<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text]  leading-tight">
            {{ __('Update User Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('admin.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('admin.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('admin.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
