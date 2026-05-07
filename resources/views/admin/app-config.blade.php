<x-app-layout>
    @php
        $configLinks = [
            [
                'label' => 'Annual leave rules',
                'description' => 'Allowance refresh date, base allowance, yearly increases, and maximum allowance.',
                'href' => route('admin.view-leave-rules'),
            ],
        ];
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text]  leading-tight">
            {{ __('Application Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-4xl">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-[var(--color-text)]">
                            Configuration Areas
                        </h3>

                        <p class="mt-1 text-sm text-[var(--color-subtletext)]">
                            Quick links to the places where admin-controlled app settings are managed.
                        </p>
                    </div>

                    <ul role="list"
                        class="overflow-hidden rounded-lg border border-[var(--color-border)] divide-y divide-[var(--color-border)]">
                        @foreach ($configLinks as $link)
                            <li>
                                <a href="{{ $link['href'] }}"
                                    class="group flex items-center justify-between gap-4 bg-[var(--color-card)] px-4 py-4 transition hover:bg-[var(--color-surface-alt)] sm:px-6">
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-semibold text-[var(--color-text)]">
                                                {{ $link['label'] }}
                                            </span>
                                        </span>

                                        <span class="mt-1 block text-sm text-[var(--color-subtletext)]">
                                            {{ $link['description'] }}
                                        </span>
                                    </span>

                                    <svg class="h-5 w-5 shrink-0 text-[var(--color-subtletext)] transition group-hover:translate-x-1 group-hover:text-[var(--color-text)]"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>

        </div>

    </div>

</x-app-layout>
