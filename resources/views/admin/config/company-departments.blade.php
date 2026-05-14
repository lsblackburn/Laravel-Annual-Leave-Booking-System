<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text]  leading-tight">
            {{ __('Modify Company Departments') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
                    <div>
                        @include('admin.partials.add-user-department')
                    </div>

                    <section>
                        <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-[--color-text]">
                                    {{ __('Added departments') }}
                                </h2>

                                <p class="mt-1 text-sm text-[--color-subtletext]">
                                    {{ __('Departments currently available when assigning users.') }}
                                </p>
                            </div>

                            <span
                                class="inline-flex w-fit items-center rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] px-3 py-1 text-xs font-semibold uppercase tracking-widest text-[var(--color-subtletext)]">
                                {{ $departments->total() }}
                                {{ \Illuminate\Support\Str::plural('department', $departments->total()) }}
                            </span>
                        </header>

                        <div
                            class="mt-6 overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-card)]">
                            @if ($departments->count() > 0)
                                <ul role="list" class="divide-y divide-[var(--color-border)]">
                                    @foreach ($departments as $department)
                                        <li
                                            class="flex items-center justify-between gap-4 px-4 py-4 transition hover:bg-[var(--color-surface-alt)] sm:px-5">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <span
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[var(--color-primary)] text-sm font-semibold uppercase text-[var(--color-background)]">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($department->department, 0, 1)) }}
                                                </span>

                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-[var(--color-text)]">
                                                        {{ $department->department }}
                                                    </p>

                                                    <p class="mt-0.5 text-xs text-[var(--color-subtletext)]">
                                                        {{ __('Company department') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button" x-data=""
                                                    x-on:click.prevent="$dispatch('open-modal', 'confirm-department-deletion-{{ $department->id }}')"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-subtletext)] transition hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-danger)] focus:outline-none focus:ring-2 focus:ring-[var(--color-danger)] focus:ring-offset-2">


                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-trash-icon lucide-trash">
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                                        <path d="M3 6h18" />
                                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('Delete department') }}</span>
                                                </button>

                                                <x-confirm-dialog
                                                    name="confirm-department-deletion-{{ $department->id }}"
                                                    :title="__('Delete department?')" :description="__(
                                                        'This department will be permanently removed from the company departments list.',
                                                    )" :action="route('admin.company-departments.delete', $department)"
                                                    method="delete">
                                                    <p class="text-sm font-medium text-[var(--color-text)]">
                                                        {{ $department->department }}
                                                    </p>
                                                </x-confirm-dialog>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="px-4 py-10 text-center sm:px-6">
                                    <div
                                        class="mx-auto flex h-11 w-11 items-center justify-center rounded-md bg-[var(--color-surface-alt)] text-[var(--color-subtletext)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 21h18" />
                                            <path d="M5 21V7l8-4v18" />
                                            <path d="M19 21V11l-6-4" />
                                            <path d="M9 9v.01" />
                                            <path d="M9 12v.01" />
                                            <path d="M9 15v.01" />
                                            <path d="M9 18v.01" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-[var(--color-text)]">
                                        {{ __('No departments added') }}
                                    </h3>

                                    <p class="mt-1 text-sm text-[var(--color-subtletext)]">
                                        {{ __('Create your first department using the form on this page.') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <x-pagination :items="$departments" />

</x-app-layout>
