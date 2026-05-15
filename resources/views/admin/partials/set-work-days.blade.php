<section>
    <header>
        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Working Days') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __('Choose the days that count as working days for annual leave.') }}
        </p>
    </header>

    <form method="post" action="{{ route('admin.work-days.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('PATCH')

        <fieldset>
            <legend class="sr-only">{{ __('Working days') }}</legend>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($workDays as $workDay)
                    <label
                        for="work_day_{{ $workDay->id }}"
                        class="group relative flex min-h-12 cursor-pointer items-center justify-center rounded-md px-4 py-3 text-sm font-semibold transition focus-within:ring-2 focus-within:ring-[--color-primary] focus-within:ring-offset-2"
                    >
                        <input
                            id="work_day_{{ $workDay->id }}"
                            type="checkbox"
                            name="work_days[]"
                            value="{{ $workDay->id }}"
                            class="peer sr-only"
                            @checked(in_array((string) $workDay->id, old('work_days', $selectedWorkDayIds), true))
                        >

                        <span
                            class="absolute inset-0 rounded-md border border-[--color-border] bg-[--color-background] transition group-hover:bg-[--color-surface-alt] peer-checked:border-[--color-primary] peer-checked:bg-[--color-primary] peer-checked:shadow-sm peer-checked:group-hover:bg-[--color-primary]"
                            aria-hidden="true"
                        ></span>

                        <span class="relative z-10 text-[--color-text] transition peer-checked:text-[--color-background]">
                            {{ $workDay->day }}
                        </span>
                    </label>
                @endforeach
            </div>

            <p class="mt-2 text-xs text-[--color-subtletext]">
                {{ __('Selected days are highlighted. At least one working day must be selected.') }}
            </p>

            <x-input-error :messages="$errors->get('work_days')" class="mt-2" />
            <x-input-error :messages="$errors->get('work_days.*')" class="mt-2" />
        </fieldset>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
