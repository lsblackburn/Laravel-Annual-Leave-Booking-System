<div
    id="popup-modal"
    tabindex="-1"
    x-data="{ open: false, form: null }"
    x-show="open"
    x-on:confirm-cancel-leave.window="open = true; form = $event.detail.form"
    x-on:keydown.escape.window="open = false"
    x-on:click.self="open = false"
    style="display: none;"
    class="fixed inset-0 z-50 grid items-center justify-center overflow-y-auto overflow-x-hidden bg-black/40 p-4"
>
    <div class="relative w-full max-w-md">
        <div class="relative rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] p-6 shadow-xl">
            <button type="button"
                class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-subtletext)] transition hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2"
                x-on:click="open = false">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18 17.94 6M18 18 6.06 6" />
                </svg>
                <span class="sr-only">Close modal</span>
            </button>

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
                    Please confirm
                </h3>

                <p class="mb-6 text-sm leading-6 text-[var(--color-subtletext)]">
                    {{ $slot }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <button type="button"
                        x-on:click="form.submit()"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-[var(--color-danger)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-background)] shadow-sm transition hover:bg-[var(--color-danger-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-danger)] focus:ring-offset-2">
                        Yes
                    </button>

                    <button type="button"
                        x-on:click="open = false"
                        class="inline-flex items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        No
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
