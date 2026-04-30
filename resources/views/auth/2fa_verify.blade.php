@extends('layouts.cms')

@section('content')

    <section class="container w-10/12 md:w-full mx-auto bg-[var(--color-card)] py-6 md:py-12 px-4 md:px-8 rounded-2xl mt-25 mb-10 md:mt-30 md:mb-12">

        <h1 class="text-3xl md:text-4xl font-bold m-0 text-center text-[var(--color-text)]">Enter Your 2FA Code</h1>

    </section>

    <section class="container w-10/12 md:w-full mx-auto bg-[var(--color-card)] py-6 md:py-12 px-4 md:px-8 rounded-2xl mb-10 md:mb-12">

        <form method="POST" action="{{ route('2fa') }}">
            @csrf

            <p class="mb-2">Please enter the  <strong>OTP</strong> generated on your Authenticator App.</p>
            
            <p class="mb-8">Ensure you submit the current one because it refreshes every 30 seconds.</p>

            <div class="mb-8 relative">

                <label for="otp">Enter the 6-digit OTP from your app:</label>

                <input type="number" name="one_time_password" id="one_time_password" class="w-full bg-[var(--color-background)] mt-2 py-3 px-4 rounded-xl" required>

            </div>
            
            @if (count($errors) > 0)
                @foreach ($errors->all() as $error)
                    <p class="text-danger">{{ $error }}</p>
                @endforeach
            @endif

            <button class="w-full bg-[var(--color-background)] text-[var(--color-button-text)] py-3 px-4 rounded-full shadow-colored-hover cursor-pointer" type="submit">Verify</button>
        </form>

    </section>

@endsection