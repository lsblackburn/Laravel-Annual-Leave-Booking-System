@props(['items'])

@if ($items->lastPage() > 1)
    <nav class="mx-auto mt-8 max-w-7xl px-3 sm:px-6 lg:px-8" role="navigation" aria-label="Pagination Navigation">
        <div
            class="flex flex-col items-center justify-between gap-3 rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] px-4 py-3 text-sm shadow-sm sm:flex-row">
            <p class="text-center text-xs text-[var(--color-subtletext)] sm:text-left">
                Showing
                <span class="font-medium text-[var(--color-text)]">{{ $items->firstItem() }}</span>
                to
                <span class="font-medium text-[var(--color-text)]">{{ $items->lastItem() }}</span>
                of
                <span class="font-medium text-[var(--color-text)]">{{ $items->total() }}</span>
            </p>

            <div class="flex flex-wrap items-center justify-center gap-1.5">
                @if ($items->onFirstPage())
                    <span
                        class="inline-flex h-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] px-3 text-xs font-semibold uppercase tracking-widest text-[var(--color-subtletext)] opacity-60">
                        Prev
                    </span>
                @else
                    <a href="{{ $items->previousPageUrl() }}" rel="prev"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] px-3 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        Prev
                    </a>
                @endif

                @if ($items->currentPage() > 3)
                    <a href="{{ $items->url(1) }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] text-sm font-medium text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        1
                    </a>

                    @if ($items->currentPage() > 4)
                        <span
                            class="inline-flex h-9 items-center px-1 text-sm text-[var(--color-subtletext)] select-none">
                            &hellip;
                        </span>
                    @endif
                @endif

                @for ($page = max(1, $items->currentPage() - 1); $page <= min($items->lastPage(), $items->currentPage() + 1); $page++)
                    @if ($page === $items->currentPage())
                        <span aria-current="page"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-transparent bg-[var(--color-primary)] text-sm font-semibold text-[var(--color-background)] shadow-sm">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $items->url($page) }}"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] text-sm font-medium text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                @if ($items->currentPage() < $items->lastPage() - 2)
                    @if ($items->currentPage() < $items->lastPage() - 3)
                        <span
                            class="inline-flex h-9 items-center px-1 text-sm text-[var(--color-subtletext)] select-none">
                            &hellip;
                        </span>
                    @endif

                    <a href="{{ $items->url($items->lastPage()) }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] text-sm font-medium text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        {{ $items->lastPage() }}
                    </a>
                @endif

                @if ($items->hasMorePages())
                    <a href="{{ $items->nextPageUrl() }}" rel="next"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] px-3 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        Next
                    </a>
                @else
                    <span
                        class="inline-flex h-9 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] px-3 text-xs font-semibold uppercase tracking-widest text-[var(--color-subtletext)] opacity-60">
                        Next
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
