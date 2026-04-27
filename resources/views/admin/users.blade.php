<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <div class="overflow-x-auto rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] shadow-sm">
            <table class="min-w-full text-sm text-left">

                <!-- Header -->
                <thead class="bg-[var(--color-surface-alt)] text-xs uppercase tracking-wider text-[var(--color-subtletext)]">
                    <tr>
                        <th class="px-6 py-3 font-medium">User</th>
                        <th class="px-6 py-3 font-medium">Email</th>
                        <th class="px-6 py-3 font-medium">Role</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody class="divide-y divide-[var(--color-border)] text-[var(--color-text)]">

                    @foreach ($users as $user)
                        <tr class="hover:bg-[var(--color-surface-alt)] transition">
                            
                            <td class="px-6 py-4 font-medium">
                                {{ $user->name }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $user->email }}
                            </td>

                            <td class="px-6 py-4">
                                {{ \Illuminate\Support\Str::ucfirst($user->role) }}
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
