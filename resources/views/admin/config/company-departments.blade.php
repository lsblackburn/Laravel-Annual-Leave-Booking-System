<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('Modify Company Departments') }}
        </h2>
    </x-slot>

    <main class="py-12">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            @include('admin.partials.add-user-department')
        </div>
    </main>

    <x-pagination :items="$departments" />
</x-app-layout>
