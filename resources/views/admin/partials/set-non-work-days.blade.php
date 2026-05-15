<section>
    <div class="grid gap-8 lg:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
        <section aria-labelledby="add-non-work-day-heading">
            <header>
                <h2 id="add-non-work-day-heading" class="text-lg font-medium text-[--color-text]">
                    {{ __('Add non-work day') }}
                </h2>

                <p class="mt-1 text-sm text-[--color-subtletext]">
                    {{ __('Add bank holidays or company closure days that should not use leave allowance.') }}
                </p>
            </header>

            <form method="post" action="{{ route('admin.non-work-days.create') }}" class="mt-6 space-y-6">
                @csrf

                <div>
                    <x-input-label for="non_work_day_name" :value="__('Name')" />
                    <x-text-input id="non_work_day_name" class="block mt-1 w-full" type="text" name="name"
                        :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="date" :value="__('Date')" />
                    <x-text-input id="date" class="block mt-1 w-full" type="text" :value="old('date')"
                        name="date" autofocus />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Add') }}</x-primary-button>
                </div>
            </form>
        </section>

        <section aria-labelledby="non-work-days-heading">
            <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="non-work-days-heading" class="text-lg font-medium text-[--color-text]">
                        {{ __('Non-work days') }}
                    </h2>

                    <p class="mt-1 text-sm text-[--color-subtletext]">
                        {{ __('These dates are excluded when leave allowance is calculated.') }}
                    </p>

                    <p class="mt-1 text-sm text-[--color-subtletext]">
                        {{ __('The below dates only show the current annual leave allowance year.') }}
                    </p>
                </div>
            </header>

            <div class="mt-6 overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-card)]">
                @if ($nonWorkDay->count() > 0)
                    <ul role="list" class="divide-y divide-[var(--color-border)]">
                        @foreach ($nonWorkDay as $day)
                            <li
                                class="flex items-center justify-between gap-4 px-4 py-4 transition hover:bg-[var(--color-surface-alt)] sm:px-5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[var(--color-text)]">
                                        {{ $day->name }}
                                    </p>

                                    <time datetime="{{ $day->date }}"
                                        class="mt-0.5 block text-xs text-[var(--color-subtletext)]">
                                        {{ \Illuminate\Support\Carbon::parse($day->date)->format('l, j F Y') }}
                                    </time>
                                </div>

                                <div>
                                    <button type="button" x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-non-work-day-deletion-{{ $day->id }}')"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-subtletext)] transition hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-danger)] focus:outline-none focus:ring-2 focus:ring-[var(--color-danger)] focus:ring-offset-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                            <path d="M3 6h18" />
                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        </svg>
                                        <span class="sr-only">{{ __('Delete non-work day') }}</span>
                                    </button>

                                    <x-confirm-dialog name="confirm-non-work-day-deletion-{{ $day->id }}"
                                        :title="__('Delete non-work day?')" :description="__('This date will be removed from the non-work days list.')" :action="route('admin.non-work-days.delete', $day)" method="delete">
                                        <p class="text-sm font-medium text-[var(--color-text)]">
                                            {{ $day->name }}
                                        </p>
                                    </x-confirm-dialog>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <section class="px-4 py-10 text-center sm:px-6" aria-labelledby="empty-non-work-days-heading">
                        <div
                            class="mx-auto flex h-11 w-11 items-center justify-center rounded-md bg-[var(--color-surface-alt)] text-[var(--color-subtletext)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M8 2v4" />
                                <path d="M16 2v4" />
                                <rect width="18" height="18" x="3" y="4" rx="2" />
                                <path d="M3 10h18" />
                            </svg>
                        </div>

                        <h3 id="empty-non-work-days-heading"
                            class="mt-4 text-sm font-semibold text-[var(--color-text)]">
                            {{ __('No non-work days added') }}
                        </h3>

                        <p class="mt-1 text-sm text-[var(--color-subtletext)]">
                            {{ __('Add a non-work day using the form on this page.') }}
                        </p>
                    </section>
                @endif
            </div>
        </section>
    </div>
</section>
