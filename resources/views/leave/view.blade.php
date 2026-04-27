<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('Your Annual Leave') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

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
                            <td class="px-6 py-4 text-right text-sm text-[var(--color-subtletext)]">
                                No actions available
                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

    </div>
    
</x-app-layout>
