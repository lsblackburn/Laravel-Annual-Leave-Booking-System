<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('Leave Requests') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <div class="overflow-x-auto rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] shadow-sm">
            <table class="min-w-full text-sm text-left">

                <!-- Header -->
                <thead class="bg-[var(--color-surface-alt)] text-xs uppercase tracking-wider text-[var(--color-subtletext)]">
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

                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium 
                                    {{ $statusColors[strtolower($request->status)] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right text-sm text-[var(--color-subtletext)] flex flex-row flex-wrap justify-end gap-3">
                                
                                <form action="{{ route('admin.leave-requests.accept', $request->id) }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" name="user_id" value="{{ $request->user_id }}">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-[--color-success] border border-transparent rounded-md font-semibold text-xs text-[--color-background] uppercase tracking-widest hover:bg-[--color-success-text] focus:bg-[--color-primary-hover] active:bg-[--color-primary-hover] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2 transition ease-in-out duration-150">
                                        Accept
                                    </button>
                                </form>

                                <form action="{{ route('admin.leave-requests.decline', $request->id) }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" name="user_id" value="">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-[--color-danger] border border-transparent rounded-md font-semibold text-xs text-[--color-background] uppercase tracking-widest hover:bg-[--color-danger-text] focus:bg-[--color-primary-hover] active:bg-[--color-primary-hover] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2 transition ease-in-out duration-150">
                                        Decline
                                    </button>
                                </form>

                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

    </div>

</x-app-layout>
