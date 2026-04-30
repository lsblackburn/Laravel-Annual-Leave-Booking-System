<x-app-layout>

@section('content')

<x-slot name="header">
    <h2 class="font-semibold text-xl text-[--color-text]  leading-tight">
        {{ __('Disable Two-Factor Authentication') }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
            <div class="w-full">
                <section>
                    <h2 class="text-lg font-medium text-[--color-text]">
                        {{ __('Please enter the OTP generated on your Authenticator App.') }}
                    </h2>

                    <p class="mt-1 text-sm text-[--color-subtletext]">
                        {{ __("Ensure you submit the current one because it refreshes every 30 seconds.") }}
                    </p>

                    <p class="mt-1 text-sm text-[--color-subtletext]">
                        {{ __("Are you sure you want to disable 2FA?") }}
                    </p>

                    <p class="mt-1 text-sm text-[--color-subtletext]">
                        {{ __("This will drastically reduce the security of your account.") }}
                    </p>
                </section>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
            <div class="max-w-xl">
                <section>

                    <h2 class="text-lg font-medium text-[--color-text]">
                        {{ __('Enter OTP to disable 2FA') }}
                    </h2>

                    <form method="POST" action="{{ route('2fa.disable') }}" class="mt-4">
                        @csrf

                        <div class="mb-8 relative">

                            <x-input-label for="otp" :value="__('One Time Password (OTP)')" />

                            <x-text-input id="otp" type="text" name="otp" class="mt-1 block w-full" required />

                        </div>

                        <x-primary-button type="submit">
                            {{ __('Disable 2FA') }}
                        </x-primary-button>
                    </form>

                </section>
            </div>
        </div>
    </div>
</div>

</x-app-layout>
