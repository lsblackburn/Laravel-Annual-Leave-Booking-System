@props(['notification'])

<form method="POST" action="{{ route('notifications.open', $notification) }}">
    @csrf
    @method('PATCH')

    <button type="submit"
        class="block w-full px-4 py-3 text-left transition hover:bg-[var(--color-surface-alt)] focus:bg-[var(--color-surface-alt)] focus:outline-none">
        <span class="block text-sm font-semibold text-[var(--color-text)]">
            {{ $notification->data['title'] ?? __('Notification') }}
        </span>

        <span class="mt-1 block text-xs leading-5 text-[var(--color-subtletext)]">
            {{ $notification->data['body'] ?? '' }}
        </span>

        <span class="mt-2 block text-xs text-[var(--color-subtletext)]">
            {{ $notification->created_at->diffForHumans() }}
        </span>
    </button>
</form>
