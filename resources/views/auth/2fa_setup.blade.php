@extends('layouts.cms')

@section('content')

<section class="container w-10/12 md:w-full mx-auto bg-[var(--color-card)] py-6 md:py-12 px-4 md:px-8 rounded-2xl mt-25 mb-10 md:mt-30 md:mb-12">

    <h1 class="text-3xl md:text-4xl font-bold m-0 text-center text-[var(--color-text)]">Enable Two-Factor Authentication</h1>

</section>

<section class="container w-10/12 md:w-full mx-auto bg-[var(--color-card)] py-6 md:py-12 px-4 md:px-8 rounded-2xl mb-10 md:mb-12">

    <h2 class="text-xl md:text-2xl mb-1 text-[var(--color-subtletext)]">Step 1</h2>

    <p class="text-xl md:text-2xl font-bold mb-5 text-[var(--color-text)]">Download app</p>

    <p class="text-xs md:text-base">Download your preferred mobile authenticator app (e.g., Google Authenticator).</p>

</section>

<section class="container w-10/12 md:w-full mx-auto bg-[var(--color-card)] py-6 md:py-12 px-4 md:px-8 rounded-2xl mb-10 md:mb-12">

    <h2 class="text-xl md:text-2xl mb-1 text-[var(--color-subtletext)]">Step 2</h2>

    <p class="text-xl md:text-2xl font-bold mb-5 text-[var(--color-text)]">Scan the QR Code</p>

    <div class="grid grid-cols-1 gap-10 md:gap-0 md:grid-cols-2">

        <div>

            <p class="mb-2 text-xs md:text-base">Scan the QR code using a mobile authentication app to genrate a verification code.</p>

            <p class="mb-8 text-xs md:text-base">Set up your two factor authentication by scanning the QR code.</p>
            
            <p class="mb-8 text-xs md:text-base">Alternatively, you can use the code <strong>{{ $secret }}</strong> within the authenticator.</p>

            <p class="text-xs md:text-base">Ensure you submit the current one because it refreshes every 30 seconds.</p>

        </div>

        <div class="flex justify-center">
            <img class="w-6/12" src="data:image/svg+xml;base64,{{ $qrCodeSvg }}" alt="QR Code">
        </div>

    </div>


</section>

<section class="container w-10/12 md:w-full mx-auto bg-[var(--color-card)] py-6 md:py-12 px-4 md:px-8 rounded-2xl mb-10 md:mb-12">

    <h2 class="text-xl md:text-2xl mb-1 text-[var(--color-subtletext)]">Step 3</h2>

    <p class="text-xl md:text-2xl font-bold mb-5 text-[var(--color-text)]">Enter the verification code</p>


    <form method="POST" action="{{ route('2fa.enable') }}" class="mt-4">
        @csrf

        <div class="mb-8 relative">

            <label for="otp" class="text-xs md:text-base">Enter OTP from app:</label>

            <input type="number" name="otp" id="otp" class="w-full bg-[var(--color-background)] mt-2 py-3 px-4 rounded-xl" required>

        </div>

        @error('otp')
            <span role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror

        @if (session('error') && !$errors->has('otp'))
            <span role="alert" style="display: block;">
                <strong>{{ session('error') }}</strong>
            </span>
        @endif
        
        <button class="w-full bg-[var(--color-background)] text-[var(--color-button-text)] py-3 px-4 rounded-full shadow-colored-hover cursor-pointer" type="submit">Enable 2FA</button>
    </form>

</section>

@endsection