<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4">

        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Enter Your 2FA Code') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __("Please enter the OTP generated on your Authenticator App.") }}
        </p>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __("Ensure you submit the current one because it refreshes every 30 seconds.") }}
        </p>

    </div>

    <form method="POST" action="{{ route('2fa') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="one_time_password" :value="__('2FA Code')" />
            <x-text-input id="one_time_password" class="block mt-1 w-full" type="text" name="one_time_password" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('one_time_password')" class="mt-2" />
        </div>
 
        <x-primary-button class="mt-3">
            {{ __('Verify') }}
        </x-primary-button>
 
    </form>

</x-guest-layout>
