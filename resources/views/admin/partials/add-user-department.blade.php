<section>
    <header>
        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Add new department') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __('Input the name of the department and click add to create a new department') }}
        </p>
    </header>

    <form method="post" action="{{ route('admin.company-departments.create') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="department" :value="__('Department')" />
            <x-text-input id="department" class="block mt-1 w-full" type="text" required name="department" min="0"
                step="0.5" :value="old('department', $settings->department ?? '')" />
            <x-input-error :messages="$errors->get('department')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Add') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

</section>
