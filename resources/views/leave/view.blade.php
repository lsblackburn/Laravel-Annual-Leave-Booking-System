<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight mb-2">
            {{ __('Your Annual Leave') }}
        </h2>
    </x-slot>

    <x-dialog-box>
        Are you sure you want to cancel this leave request?
    </x-dialog-box>

    <div class="max-w-7xl mx-auto py-6 px-3 sm:px-6 lg:px-8">
        <div class="overflow-x-auto rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] shadow-sm">
            <div class="p-4 sm:p-6 bg-[--color-card] shadow sm:rounded-lg">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div>
                        <p class="text-sm text-center sm:text-left text-[--color-subtletext]">Your Leave Allowance</p>
                        <p
                            class="text-xl md:text-2xl lg:text-3xl text-center sm:text-left font-semibold text-[--color-text]">
                            {{ Auth::user()->leave_allowance }} days
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-center sm:text-right text-[--color-subtletext]">Remaining</p>
                        <p class="text-xl text-center sm:text-right font-medium text-[--color-primary]">
                            {{ Auth::user()->remainingLeaveAllowance() }} days
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{
        open: false,
        action: '',
        request: {},
        show(action, request) {
            this.action = action;
            this.request = request;
            this.open = true;
        },
    }" x-on:keydown.escape.window="open = false">
        <div x-show="open" x-on:click.self="open = false" style="display: none;"
            class="fixed inset-0 z-50 grid items-center justify-center overflow-y-auto overflow-x-hidden bg-black/40 p-4">
            <div class="relative w-full max-w-lg">
                <div
                    class="relative rounded-lg border border-[var(--color-border)] bg-[var(--color-card)] p-6 shadow-xl">
                    <button type="button" x-on:click="open = false"
                        class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-subtletext)] transition hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18 17.94 6M18 18 6.06 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>

                    <div class="flex items-start gap-3 pr-10">
                        <div
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-[var(--color-surface-alt)] text-[var(--color-primary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path
                                    d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719" />
                                <path d="M8 12h.01" />
                                <path d="M12 12h.01" />
                                <path d="M16 12h.01" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-[var(--color-text)]">
                                Manager Comment
                            </h3>
                            <p class="mt-1 text-sm text-[var(--color-subtletext)]">
                                Feedback added when your request was reviewed.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-5 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] p-4">
                        <p class="whitespace-pre-line text-sm leading-6 text-[var(--color-text)]"
                            x-text="request.manager_comment"></p>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" x-on:click="open = false"
                            class="inline-flex items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto py-6 px-3 sm:px-6 lg:px-8">

            <div
                class="overflow-x-auto rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] shadow-sm">
                <table class="min-w-full text-sm text-left">

                    <!-- Header -->
                    <thead
                        class="bg-[var(--color-surface-alt)] text-xs uppercase tracking-wider text-[var(--color-subtletext)]">
                        <tr>
                            <th class="px-6 py-3 font-medium">Leave Type</th>
                            <th class="px-6 py-3 font-medium">Start Date</th>
                            <th class="px-6 py-3 font-medium">End Date</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="divide-y divide-[var(--color-border)] text-[var(--color-text)]">

                        @if ($leaveRequests->isEmpty())
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-[var(--color-subtletext)]">
                                    No leave requests found.
                                </td>
                            </tr>
                        @else
                            @foreach ($leaveRequests as $request)
                                <tr class="hover:bg-[var(--color-surface-alt)] transition">

                                    <td class="px-6 py-4">
                                        {{ $request->is_half_day ? 'Half Day' : 'Full Day(s)' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ \Carbon\Carbon::parse($request->start_date)->format('d/m/Y') }}
                                    </td>

                                    <td class="px-6 py-4">
                                        {{ \Carbon\Carbon::parse($request->end_date)->format('d/m/Y') }}
                                    </td>

                                    <!-- Status badge -->
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-amber-100 text-amber-700',
                                                'approved' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp

                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium 
                                            {{ $statusColors[strtolower($request->status)] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td
                                        class="px-6 py-4 text-right text-sm text-[var(--color-subtletext)] flex flex-row flex-wrap justify-end gap-3">

                                        @if ($request->status == 'pending')
                                            <x-primary-link
                                                href="{{ route('leave.edit', ['request' => $request->id]) }}">
                                                Modify
                                            </x-primary-link>

                                            <form x-data action="{{ route('leave.delete', $request->id) }}"
                                                method="POST"
                                                x-on:submit.prevent="$dispatch('confirm-cancel-leave', { form: $event.target })">
                                                @csrf
                                                @method('DELETE')

                                                <x-primary-button>
                                                    Cancel
                                                </x-primary-button>
                                            </form>
                                        @else
                                            @if (!empty($request->manager_comment))
                                                <button type="button"
                                                    data-manager-comment="{{ $request->manager_comment }}"
                                                    x-on:click="show('', {
                                                        manager_comment: $el.dataset.managerComment,
                                                    })"
                                                    class="inline-flex items-center px-2 md:px-4 py-2 bg-[--color-primary] border border-transparent rounded-md font-semibold text-xs text-[--color-background] uppercase tracking-widest hover:bg-[--color-primary-hover] focus:bg-[--color-primary-hover] active:bg-[--color-primary-hover] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2 transition ease-in-out duration-150">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        class="lucide lucide-message-circle-more-icon lucide-message-circle-more">
                                                        <path
                                                            d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719" />
                                                        <path d="M8 12h.01" />
                                                        <path d="M12 12h.01" />
                                                        <path d="M16 12h.01" />
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="text-gray-400">
                                                    No actions available
                                                </span>
                                            @endif
                                        @endif

                                    </td>

                                </tr>
                            @endforeach
                        @endif

                        <tr class="hover:bg-[var(--color-surface-alt)] transition">
                            <td colspan="5" class="text-center text-sm text-[var(--color-subtletext)]">
                                <a href="{{ route('leave.form') }}"
                                    class="inline-flex items-center justify-center cursor-pointer w-full gap-2 px-6 py-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-plus-icon lucide-plus">
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>

                                    Create new leave request
                                </a>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <x-pagination :items="$leaveRequests" />

</x-app-layout>
