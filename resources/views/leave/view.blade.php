<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('Your Annual Leave') }}
        </h2>
    </x-slot>

    <x-dialog-box>
        Are you sure you want to cancel this leave request?
    </x-dialog-box>

    <div class="max-w-7xl mx-auto py-6 px-3 sm:px-6 lg:px-8">

        <div class="overflow-x-auto rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] shadow-sm">
            <table class="min-w-full text-sm text-left">

                <!-- Header -->
                <thead class="bg-[var(--color-surface-alt)] text-xs uppercase tracking-wider text-[var(--color-subtletext)]">
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

                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium 
                                        {{ $statusColors[strtolower($request->status)] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-sm text-[var(--color-subtletext)] flex flex-row flex-wrap justify-end gap-3">

                                    @if ($request->status == 'pending')
                                        <x-primary-link href="{{ route('leave.edit', ['request' => $request->id]) }}">
                                            Modify
                                        </x-primary-link>
                                    @endif

                                    <form
                                        x-data
                                        action="{{ route('leave.delete', $request->id) }}"
                                        method="POST"
                                        x-on:submit.prevent="$dispatch('confirm-cancel-leave', { form: $event.target })"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <x-primary-button>
                                            Cancel
                                        </x-primary-button>
                                    </form>

                                </td>

                            </tr>
                        @endforeach
                    @endif

                    <tr class="hover:bg-[var(--color-surface-alt)] transition">
                        <td colspan="5" class="text-center text-sm text-[var(--color-subtletext)]">
                            <a href="{{ route('leave.form') }}" class="inline-flex items-center justify-center cursor-pointer w-full gap-2 px-6 py-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-icon lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>

                                Create new leave request
                            </a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>
    
</x-app-layout>
