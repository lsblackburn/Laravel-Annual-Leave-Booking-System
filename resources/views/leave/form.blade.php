<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[--color-text] leading-tight mb-2">
            {{ __('Annual Leave') }}
        </h2>
    </x-slot>

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

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-[--color-card] shadow sm:rounded-lg">
                <div class="max-w-xl">

                    <h2 class="text-3xl font-medium text-[--color-text]">
                        Annual Leave Request Details
                    </h2>

                    <form method="POST" action="{{ route('leave.create') }}" x-data="{
                        startDate: @js(old('start_date', '')),
                        endDate: @js(old('end_date', '')),
                        halfDay: @js((bool) old('is_half_day', false)),
                        syncHalfDayEndDate() {
                            if (this.halfDay) {
                                this.endDate = this.startDate;
                                this.$nextTick(() => this.$refs.endDate.blur());
                            }
                        },
                    }"
                        x-init="$watch('startDate', () => syncHalfDayEndDate());
                        $watch('halfDay', () => syncHalfDayEndDate())" class="mt-6 space-y-6">

                        @csrf

                        <div class="mt-5">
                            <x-input-label for="start_date" :value="__('Start Date*')" />
                            <x-text-input id="start_date" class="block mt-1 w-full" type="text" name="start_date"
                                x-model="startDate" required autofocus />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="end_date" :value="__('End Date*')" />
                            <x-text-input id="end_date" class="block mt-1 w-full" type="text" name="end_date"
                                x-ref="endDate" x-model="endDate" x-bind:readonly="halfDay"
                                x-bind:tabindex="halfDay ? -1 : 0"
                                x-bind:class="{ 'cursor-not-allowed opacity-75 pointer-events-none': halfDay }" required
                                autofocus />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>

                        <div class="block mt-5">
                            <label for="is_half_day" class="inline-flex items-center">
                                <input type="hidden" name="is_half_day" value="0">
                                <input id="is_half_day" value="1" type="checkbox"
                                    class="rounded bg-[--color-background] border-[--color-border] text-[--color-primary] shadow-sm focus:ring-[--color-primary]"
                                    name="is_half_day" x-model="halfDay">
                                <span
                                    class="ms-2 text-sm text-[--color-subtletext]">{{ __('Is this a half-day leave?') }}</span>
                            </label>
                        </div>

                        <div class="mt-5">
                            <x-input-label for="reason" :value="__('Reason for Leave* (255 characters max)')" />
                            <x-text-textarea id="reason" class="block mt-1 w-full" type="textarea" name="reason"
                                :value="old('reason')" required autofocus />
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="additional_info" :value="__('Additional Information (255 characters max)')" />
                            <x-text-textarea id="additional_info" class="block mt-1 w-full" type="textarea"
                                name="additional_info" :value="old('additional_info')" />
                        </div>

                        <x-primary-button>
                            {{ __('Submit Request') }}
                        </x-primary-button>

                    </form>

                </div>
            </div>

        </div>
    </div>

</x-app-layout>
