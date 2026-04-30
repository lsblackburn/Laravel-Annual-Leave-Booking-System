<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-[--color-text]">
            {{ __('Activate Two-Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-[--color-subtletext]">
            {{ __('Add an extra layer of security to your account by enabling two-factor authentication.') }}
        </p>
    </header>

    @auth
        @php $enabled = auth()->user()->hasTwoFactorEnabled(); @endphp

        <x-primary-link href="{{ $enabled ? route('2fa.disable.form') : route('2fa.setup') }}">
            {{ $enabled ? 'Disable 2FA' : 'Enable 2FA' }}
        </x-primary-link>

    @endauth

</section>
