<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('Leave Requests') }}
        </h2>
    </x-slot>

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
                    class="relative rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] p-6 shadow-xl">
                    <button type="button" x-on:click="open = false"
                        class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--color-subtletext)] transition hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18 17.94 6M18 18 6.06 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>

                    <h3 class="mb-1 pr-10 text-lg font-semibold text-[var(--color-text)]">
                        Respond to leave request
                    </h3>
                    <p class="mb-5 text-sm text-[var(--color-subtletext)]">
                        Review the request details before approving or declining.
                    </p>

                    <dl class="space-y-4 text-sm">
                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-[var(--color-subtletext)]">Employee</dt>
                            <dd class="sm:col-span-2 text-[var(--color-text)]" x-text="request.employee"></dd>
                        </div>

                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-[var(--color-subtletext)]">Leave type</dt>
                            <dd class="sm:col-span-2 text-[var(--color-text)]" x-text="request.type"></dd>
                        </div>

                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-[var(--color-subtletext)]">Dates</dt>
                            <dd class="sm:col-span-2 text-[var(--color-text)]" x-text="request.dates"></dd>
                        </div>

                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-[var(--color-subtletext)]">Reason</dt>
                            <dd class="sm:col-span-2 whitespace-pre-line text-[var(--color-text)]"
                                x-text="request.reason"></dd>
                        </div>

                        <div class="grid gap-1 sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-[var(--color-subtletext)]">Additional details</dt>
                            <dd class="sm:col-span-2 whitespace-pre-line text-[var(--color-text)]"
                                x-text="request.additionalInfo"></dd>
                        </div>
                    </dl>

                    <form x-bind:action="action" method="POST"
                        class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        @csrf
                        @method('POST')

                        <button type="button" x-on:click="open = false"
                            class="inline-flex items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-card)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[var(--color-text)] shadow-sm transition hover:bg-[var(--color-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                            Close
                        </button>

                        <button type="submit" name="response" value="rejected"
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-[--color-danger] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[--color-background] transition hover:bg-[--color-danger-text] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2">
                            Decline
                        </button>

                        <button type="submit" name="response" value="approved"
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-[--color-success] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[--color-background] transition hover:bg-[--color-success-text] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2">
                            Approve
                        </button>
                    </form>
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
                            <th class="px-6 py-3 font-medium">Employee</th>
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
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-[var(--color-subtletext)]">
                                    No leave requests found.
                                </td>
                            </tr>
                        @else
                            @foreach ($leaveRequests as $request)
                                <tr class="hover:bg-[var(--color-surface-alt)] transition">

                                    <td class="px-6 py-4 font-medium">
                                        {{ $request->user_name }}
                                    </td>

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
                                        <button type="button"
                                            data-action="{{ route('admin.leave-requests.response', $request->id) }}"
                                            data-employee="{{ $request->user_name }}"
                                            data-type="{{ $request->is_half_day ? 'Half Day' : 'Full Day(s)' }}"
                                            data-dates="{{ \Carbon\Carbon::parse($request->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($request->end_date)->format('d/m/Y') }}"
                                            data-reason="{{ $request->reason ?: 'No reason provided.' }}"
                                            data-additional-info="{{ $request->additional_info ?: 'No additional details provided.' }}"
                                            x-on:click="show($el.dataset.action, {
                                            employee: $el.dataset.employee,
                                            type: $el.dataset.type,
                                            dates: $el.dataset.dates,
                                            reason: $el.dataset.reason,
                                            additionalInfo: $el.dataset.additionalInfo,
                                        })"
                                            class="inline-flex items-center px-4 py-2 bg-[--color-primary] border border-transparent rounded-md font-semibold text-xs text-[--color-background] uppercase tracking-widest hover:bg-[--color-primary-hover] focus:bg-[--color-primary-hover] active:bg-[--color-primary-hover] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2 transition ease-in-out duration-150">
                                            Respond
                                        </button>

                                    </td>

                                </tr>
                            @endforeach

                        @endif

                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <x-pagination :items="$leaveRequests" />

</x-app-layout>
