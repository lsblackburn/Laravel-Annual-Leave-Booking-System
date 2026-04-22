<div
    x-data="{
        open: false,
        dark: false,

        init() {
            this.dark = document.documentElement.getAttribute('data-theme') === 'dark';
        },

        toggle() {
            document.documentElement.setAttribute(
                'data-theme',
                this.dark ? 'dark' : 'light'
            );
        }
    }"
    x-init="init()"
    class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-3"
>
    <!-- Panel -->
    <div
        x-show="open"
        @click.outside="open = false"
        x-transition
        class="w-72 rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] p-5 shadow-xl"
    >
        <h2 class="mb-4 text-lg font-semibold text-[var(--color-text)]">
            Settings
        </h2>

        <!-- Theme toggle -->
        <label class="flex items-center justify-between cursor-pointer">
            <span class="text-sm text-[var(--color-text)]">Dark Mode</span>

            <div class="relative">
                <input
                    type="checkbox"
                    x-model="dark"
                    @change="toggle()"
                    class="peer sr-only"
                />

                <div class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[var(--color-primary)]"></div>

                <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
            </div>
        </label>
    </div>

    <!-- Button -->
    <button
        @click="open = !open"
        aria-label="Open settings"
        class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-surface-alt)] text-[var(--color-text)] shadow-md transition hover:bg-[var(--color-card)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" stroke-width="2">
            <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
</div>