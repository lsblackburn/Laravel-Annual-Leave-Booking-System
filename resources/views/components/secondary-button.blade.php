<button
    {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] bg-[var(--color-card)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition ease-in-out duration-150 hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
