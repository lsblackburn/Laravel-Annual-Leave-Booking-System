<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('Annual Leave') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">

                    <h2 class="text-3xl font-medium text-[--color-text]">
                        Annual Leave Request Details
                    </h2>

                    <form method="POST" action="" class="mt-6 space-y-6">

                        @csrf

                        <div class="mt-5">
                            <x-input-label for="start_date" :value="__('Start Date*')" />
                            <x-text-input id="start_date" class="block mt-1 w-full" type="text" name="start_date" :value="old('start_date')" required autofocus />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        
                        <div class="mt-5">
                            <x-input-label for="end_date" :value="__('End Date*')" />
                            <x-text-input id="end_date" class="block mt-1 w-full" type="text" name="end_date" :value="old('end_date')" required autofocus />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>

                        <div class="block mt-5">
                            <label for="is_half_day" class="inline-flex items-center">
                                <input id="is_half_day" type="checkbox" class="rounded bg-[--color-background] border-[--color-border] text-[--color-primary] shadow-sm focus:ring-[--color-primary]" name="is_half_day">
                                <span class="ms-2 text-sm text-[--color-subtletext]">{{ __('Is this a half-day leave?') }}</span>
                            </label>
                        </div>

                        <div class="mt-5">
                            <x-input-label for="reasoning" :value="__('Reason for Leave*')" />
                            <x-text-textarea id="reasoning" class="block mt-1 w-full" type="textarea" name="reasoning" :value="old('reasoning')" required autofocus />
                            <x-input-error :messages="$errors->get('reasoning')" class="mt-2" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="additional_info" :value="__('Additional Information')" />
                            <x-text-textarea id="additional_info" class="block mt-1 w-full" type="textarea" name="additional_info" :value="old('additional_info')" />
                        </div>

                        <x-primary-button>
                            {{ __('Submit Request') }}
                        </x-primary-button>

                    </form>

                </div>
            </div>

        </div>
    </div>
    
</x-app-layout>
