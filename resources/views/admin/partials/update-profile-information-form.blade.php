<section>
    <header>
        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __("Update this user's profile information and email address.") }}
        </p>
    </header>

    @php
        $employment_start_date = $user->employment_start_date
            ? \Carbon\Carbon::parse($user->employment_start_date)->format('d-m-Y')
            : '';
    @endphp

    <form method="post" action="{{ route('admin.users.update', $user->id) }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required
                autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)"
                required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="employment_start_date" :value="__('Employment Start Date')" />
            <x-text-input id="employment_start_date" class="block mt-1 w-full" :value="old('employment_start_date', $employment_start_date)" type="text"
                name="employment_start_date" autofocus />
            <x-input-error :messages="$errors->get('employment_start_date')" class="mt-2" />
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
