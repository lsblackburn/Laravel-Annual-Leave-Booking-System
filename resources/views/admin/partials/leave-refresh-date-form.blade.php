<section>
    <header>
        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Leave Refresh Date') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __('Set the day and month when employee leave allowance resets each year.') }}
        </p>
    </header>

    <form method="post" action="{{ route('admin.leave-refresh.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="leave_refresh_day" :value="__('Refresh Day')" />
            <x-text-input id="leave_refresh_day" class="block mt-1 w-full" type="number" required name="leave_refresh_day"
                min="1" max="31" :value="old('leave_refresh_day', $settings->leave_refresh_day ?? '')" />
            <p class="mt-1 text-xs text-[--color-subtletext]">
                Enter a day between 1 and 31.
            </p>
            <x-input-error :messages="$errors->get('leave_refresh_day')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="leave_refresh_month" :value="__('Refresh Month')" />
            <x-text-input id="leave_refresh_month" class="block mt-1 w-full" type="number" required
                name="leave_refresh_month" min="1" max="12" :value="old('leave_refresh_month', $settings->leave_refresh_month ?? '')" />
            <p class="mt-1 text-xs text-[--color-subtletext]">
                Enter a month between 1 and 12.
            </p>
            <x-input-error :messages="$errors->get('leave_refresh_month')" class="mt-2" />
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
