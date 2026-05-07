<section>
    <header>
        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Leave Allowance Rules') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __('Control the default allowance and how it increases based on length of service.') }}
        </p>
    </header>

    <form method="post" action="{{ route('admin.leave-allowance.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="base_allowance" :value="__('Base Allowance')" />
            <x-text-input id="base_allowance" class="block mt-1 w-full" type="number" required name="base_allowance"
                min="0" step="0.5" :value="old('base_allowance', $settings->base_allowance ?? '')" />
            <p class="mt-1 text-xs text-[--color-subtletext]">
                Default annual leave allowance in days.
            </p>
            <x-input-error :messages="$errors->get('base_allowance')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="maximum_allowance" :value="__('Maximum Allowance')" />
            <x-text-input id="maximum_allowance" class="block mt-1 w-full" type="number" required
                name="maximum_allowance" min="0" step="0.5" :value="old('maximum_allowance', $settings->maximum_allowance ?? '')" />
            <p class="mt-1 text-xs text-[--color-subtletext]">
                The highest allowance an employee can reach.
            </p>
            <x-input-error :messages="$errors->get('maximum_allowance')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="increase_after_years" :value="__('Increase After')" />
            <x-text-input id="increase_after_years" class="block mt-1 w-full" type="number" required
                name="increase_after_years" min="0" :value="old('increase_after_years', $settings->increase_after_years ?? '')" />
            <p class="mt-1 text-xs text-[--color-subtletext]">
                Number of years before extra leave is added.
            </p>
            <x-input-error :messages="$errors->get('increase_after_years')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="increase_by_days" :value="__('Increase By')" />
            <x-text-input id="increase_by_days" class="block mt-1 w-full" type="number" required
                name="increase_by_days" min="0" step="0.5" :value="old('increase_by_days', $settings->increase_by_days ?? '')" />
            <p class="mt-1 text-xs text-[--color-subtletext]">
                Extra days added after the year threshold.
            </p>
            <x-input-error :messages="$errors->get('increase_by_days')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
