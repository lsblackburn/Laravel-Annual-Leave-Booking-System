<x-app-layout>

@section('content')

<x-slot name="header">
    <h2 class="font-semibold text-xl text-[--color-text]  leading-tight">
        {{ __('Two Factor Authentication Setup') }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
            <div class="w-full">
                <section>
                    <h2 class="text-lg font-medium text-[--color-text]">
                        {{ __('Step 1: Download app') }}
                    </h2>

                    <p class="mt-1 text-sm text-[--color-subtletext]">
                        {{ __("Download your preferred mobile authenticator app (e.g., Google Authenticator).") }}
                    </p>
                </section>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
            <div class="w-full">
                <section>

                    <h2 class="text-lg font-medium text-[--color-text]">
                        {{ __('Step 2: Scan QR Code') }}
                    </h2>

                    <div class="grid grid-cols-1 gap-10 md:gap-0 md:grid-cols-2">

                        <div>

                            <p class="mt-1 text-sm text-[--color-subtletext]">
                                {{ __("Scan the QR code using a mobile authentication app to genrate a verification code.") }}
                            </p>

                            <p class="mt-1 text-sm text-[--color-subtletext]">
                                {{ __("Set up your two factor authentication by scanning the QR code.") }}
                            </p>

                            <p class="mt-1 text-sm text-[--color-subtletext]">
                                {{ __("Alternatively, you can use the following code within the authenticator:") }}
                            </p>

                            <p class="mt-1 text-sm font-bold text-[--color-text]">{{ $secret }}</p>

                            <p class="mt-1 text-sm text-[--color-subtletext]">
                                {{ __("Ensure you submit the current one because it refreshes every 30 seconds.") }}
                            </p>

                        </div>

                        <div class="flex justify-center">
                            <img class="w-6/12" src="data:image/svg+xml;base64,{{ $qrCodeSvg }}" alt="QR Code">
                        </div>

                    </div>

                </section>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
        <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
            <div class="max-w-xl">
                <section>

                    <h2 class="text-lg font-medium text-[--color-text]">
                        {{ __('Step 3: Enter Verification Code') }}
                    </h2>

                    <form method="POST" action="{{ route('2fa.enable') }}" class="mt-4">
                        @csrf

                        <div class="mb-8 relative">

                            <x-input-label for="otp" :value="__('One Time Password (OTP)')" />

                            <x-text-input id="otp" type="text" name="otp" class="mt-1 block w-full" :value="$secret" required />

                        </div>

                        <x-primary-button type="submit">
                            {{ __('Enable 2FA') }}
                        </x-primary-button>
                    </form>

                </section>
            </div>
        </div>
    </div>
</div>

</x-app-layout>
