<div class="max-w-7xl mx-auto py-6 px-3 sm:px-6 lg:px-8">
    <div class="overflow-x-auto rounded-lg border border-[var(--color-border)] bg-[var(--color-card)] shadow-sm">
        <div class="p-4 sm:p-6 bg-[--color-card] shadow rounded-lg">
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
                    @if (Auth::user()->calculatePendingLeaveNumber() != 0)
                        <p class="text-xs text-center sm:text-right text-[--color-primary]">Pending
                            {{ Auth::user()->calculatePendingLeaveNumber() }} days</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
