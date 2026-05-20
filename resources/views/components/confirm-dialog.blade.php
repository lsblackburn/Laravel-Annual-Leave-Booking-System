@props([
    'name',
    'title',
    'description',
    'action' => null,
    'method' => 'delete',
    'confirmText' => __('Yes'),
    'cancelText' => __('No'),
])

<div x-data="{ show: false }" x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false" x-show="show" x-on:click.self="show = false" style="display: none;"
    class="fixed inset-0 z-50 grid items-center justify-center overflow-y-auto overflow-x-hidden bg-black/40 p-4">
    <div class="relative w-full max-w-md">
        <div class="relative rounded-lg border border-[var(--color-border)] bg-[var(--color-card)] p-6 shadow-xl">
            <button type="button" x-on:click="show = false"
                class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-lg text-[var(--color-subtletext)] transition hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
                <span class="sr-only">{{ __('Close modal') }}</span>
            </button>

            @if ($action)
                <form method="post" action="{{ $action }}">
                    @csrf
                    @method($method)
            @endif

            <div class="text-center">
                <div
                    class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--color-surface-alt)] text-[var(--color-danger)]">
                    <svg class="h-8 w-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <h3 class="mb-2 text-lg font-semibold text-[var(--color-text)]">
                    {{ $title }}
                </h3>

                <div class="mb-6 text-sm leading-6 text-[var(--color-subtletext)]">
                    <p>{{ $description }}</p>

                    @if ($slot->isNotEmpty())
                        <div class="mt-2">
                            {{ $slot }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <button type="{{ $action ? 'submit' : 'button' }}"
                        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-[var(--color-danger)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-background)] shadow-sm transition hover:bg-[var(--color-danger-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-danger)] focus:ring-offset-2">
                        {{ $confirmText }}
                    </button>

                    <button type="button" x-on:click="show = false"
                        class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] bg-[var(--color-card)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        {{ $cancelText }}
                    </button>
                </div>
            </div>

            @if ($action)
                </form>
            @endif
        </div>
    </div>
</div>
