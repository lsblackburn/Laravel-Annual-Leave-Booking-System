<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\NonWorkDay;
use App\Models\User;
use App\Models\WorkDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class LeaveAllowanceService
{
    private ?array $activeWorkDayNames = null;
    private ?array $nonWorkDayDates = null;

    /**
     * Calculate the entitlement that should apply at a specific point in time.
     */
    public function calculateAllowance(User $user, ?LeaveSetting $settings = null, ?Carbon $date = null): float
    {
        $settings ??= LeaveSetting::first();
        $date ??= Carbon::now();

        // Missing settings are treated as "keep the user's stored allowance" so leave remains usable.
        if (! $settings || ! $settings->base_allowance) {
            return (float) $user->leave_allowance;
        }

        if (! $user->employment_start_date) {
            return (float) $user->leave_allowance;
        }

        $yearsWorked = (int) floor(Carbon::parse($user->employment_start_date)->diffInYears($date));

        if ($yearsWorked < $settings->increase_after_years) {
            return (float) $settings->base_allowance;
        }

        $extraYears = $yearsWorked - $settings->increase_after_years + 1;
        $allowance = $settings->base_allowance + ($extraYears * $settings->increase_by_days);

        return (float) min($allowance, $settings->maximum_allowance);
    }

    public function leaveDaysUsedForStatus(User $user, string $status): float
    {
        $settings = LeaveSetting::first();
        $allowanceYearStart = $this->allowanceYearStart($settings, Carbon::now());
        $allowanceYearEnd = $allowanceYearStart && $settings
            ? $this->allowanceYearEnd($settings, $allowanceYearStart)
            : null;

        $query = $user->leaves()->where('status', $status);

        if ($allowanceYearStart && $allowanceYearEnd) {
            $query
                ->where('end_date', '>=', $allowanceYearStart->toDateString())
                ->where('start_date', '<', $allowanceYearEnd->toDateString());
        }

        return (float) $query
            ->get()
            ->sum(fn (Leave $leave) => $this->leaveDaysWithinWindow(
                $this->leaveRequestData($leave),
                $allowanceYearStart,
                $allowanceYearEnd
            ));
    }

    public function ensureLeaveRequestFitsAllowance(
        User $user,
        array $leaveRequest,
        ?Leave $ignoredLeave = null,
        array $reservedStatuses = ['approved', 'pending']
    ): void {
        $settings = LeaveSetting::first();
        $totalRequestedDays = $this->leaveDays($leaveRequest);

        // Requests made only on inactive weekdays or configured non-work days should not be accepted.
        if ($totalRequestedDays <= 0) {
            throw ValidationException::withMessages([
                'end_date' => 'This request does not include any working days.',
            ]);
        }

        if (! $settings?->leave_refresh_day || ! $settings?->leave_refresh_month) {
            $remainingAllowance = $this->remainingLeaveAllowanceWithoutRefreshSettings($user, $reservedStatuses, $ignoredLeave);

            if ($totalRequestedDays <= $remainingAllowance) {
                return;
            }

            throw ValidationException::withMessages([
                'end_date' => "This request uses {$totalRequestedDays} days, but you only have {$remainingAllowance} days remaining.",
            ]);
        }

        $requestStart = Carbon::parse($leaveRequest['start_date'])->startOfDay();
        $requestEnd = Carbon::parse($leaveRequest['end_date'])->startOfDay();
        $allowanceYearStart = $this->allowanceYearStart($settings, $requestStart);

        // A request can cross refresh dates, so validate each allowance year segment separately.
        while ($allowanceYearStart->lte($requestEnd)) {
            $allowanceYearEnd = $this->allowanceYearEnd($settings, $allowanceYearStart);
            $requestedDays = $this->leaveDaysWithinWindow(
                $leaveRequest,
                $allowanceYearStart,
                $allowanceYearEnd
            );

            if ($requestedDays <= 0) {
                $allowanceYearStart = $allowanceYearEnd;
                continue;
            }

            $remainingAllowance = $this->remainingLeaveAllowanceForWindow(
                $user,
                $settings,
                $allowanceYearStart,
                $allowanceYearEnd,
                $reservedStatuses,
                $ignoredLeave
            );

            if ($requestedDays > $remainingAllowance) {
                throw ValidationException::withMessages([
                    'end_date' => "This request uses {$requestedDays} days in the allowance year starting {$allowanceYearStart->format('d/m/Y')}, but you only have {$remainingAllowance} days remaining for that year.",
                ]);
            }

            $allowanceYearStart = $allowanceYearEnd;
        }
    }

    public function isRefreshDate(LeaveSetting $settings, Carbon $date): bool
    {
        return $date->isSameDay($this->refreshDateForYear($settings, (int) $date->year));
    }

    public function allowanceYearStart(?LeaveSetting $settings, Carbon $date): ?Carbon
    {
        if (! $settings?->leave_refresh_day || ! $settings?->leave_refresh_month) {
            return null;
        }

        $refreshDate = $this->refreshDateForYear($settings, (int) $date->year);

        if ($date->lt($refreshDate)) {
            return $this->refreshDateForYear($settings, (int) $date->year - 1);
        }

        return $refreshDate;
    }

    public function refreshDateForYear(LeaveSetting $settings, int $year): Carbon
    {
        $month = (int) $settings->leave_refresh_month;
        // Clamp dates such as 29 February to the last valid day in non-leap years.
        $day = min((int) $settings->leave_refresh_day, Carbon::create($year, $month, 1)->daysInMonth);

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    public function leaveRequestData(Leave $leave): array
    {
        return [
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'is_half_day' => (bool) $leave->is_half_day,
        ];
    }

    private function allowanceYearEnd(LeaveSetting $settings, Carbon $allowanceYearStart): Carbon
    {
        return $this->refreshDateForYear($settings, (int) $allowanceYearStart->year + 1);
    }

    private function remainingLeaveAllowanceWithoutRefreshSettings(User $user, array $reservedStatuses, ?Leave $ignoredLeave): float
    {
        $usedDaysQuery = $user->leaves()->whereIn('status', $reservedStatuses);

        if ($ignoredLeave) {
            $usedDaysQuery->where('id', '!=', $ignoredLeave->id);
        }

        $usedDays = $usedDaysQuery
            ->get()
            ->sum(fn (Leave $leave) => $this->leaveDays($this->leaveRequestData($leave)));

        return (float) $user->leave_allowance - $usedDays;
    }

    private function remainingLeaveAllowanceForWindow(
        User $user,
        LeaveSetting $settings,
        Carbon $allowanceYearStart,
        Carbon $allowanceYearEnd,
        array $reservedStatuses,
        ?Leave $ignoredLeave
    ): float {
        $allowance = $this->allowanceForWindowStart($user, $settings, $allowanceYearStart);
        // Include pending leave as reserved allowance so overlapping requests cannot overspend entitlement.
        $usedDaysQuery = $user->leaves()
            ->whereIn('status', $reservedStatuses)
            ->where('end_date', '>=', $allowanceYearStart->toDateString())
            ->where('start_date', '<', $allowanceYearEnd->toDateString());

        if ($ignoredLeave) {
            $usedDaysQuery->where('id', '!=', $ignoredLeave->id);
        }

        $usedDays = $usedDaysQuery
            ->get()
            ->sum(fn (Leave $leave) => $this->leaveDaysWithinWindow(
                $this->leaveRequestData($leave),
                $allowanceYearStart,
                $allowanceYearEnd
            ));

        return $allowance - $usedDays;
    }

    private function allowanceForWindowStart(User $user, LeaveSetting $settings, Carbon $date): float
    {
        $currentAllowanceYearStart = $this->allowanceYearStart($settings, Carbon::now());

        // Current year balances may have been manually adjusted, so use the stored value for that window.
        if ($currentAllowanceYearStart && $date->isSameDay($currentAllowanceYearStart)) {
            return (float) $user->leave_allowance;
        }

        return $this->calculateAllowance($user, $settings, $date);
    }

    private function leaveDaysWithinWindow(array $leaveRequest, ?Carbon $windowStart, ?Carbon $windowEnd): float
    {
        if (! $windowStart || ! $windowEnd) {
            return $this->leaveDays($leaveRequest);
        }

        $requestStart = Carbon::parse($leaveRequest['start_date'])->startOfDay();
        $requestEnd = Carbon::parse($leaveRequest['end_date'])->startOfDay();

        if ($requestEnd->lt($windowStart) || $requestStart->gte($windowEnd)) {
            return 0.0;
        }

        if ($leaveRequest['is_half_day']) {
            return $this->isWorkingDate($requestStart) ? 0.5 : 0.0;
        }

        if ($requestStart->lt($windowStart)) {
            $requestStart = $windowStart->copy();
        }

        if ($requestEnd->gte($windowEnd)) {
            $requestEnd = $windowEnd->copy()->subDay();
        }

        return $this->workDaysBetween($requestStart, $requestEnd);
    }

    private function leaveDays(array $leaveRequest): float
    {
        $startDate = Carbon::parse($leaveRequest['start_date'])->startOfDay();
        $endDate = Carbon::parse($leaveRequest['end_date'])->startOfDay();

        if ($leaveRequest['is_half_day']) {
            return $this->isWorkingDate($startDate) ? 0.5 : 0.0;
        }

        return $this->workDaysBetween($startDate, $endDate);
    }

    private function workDaysBetween(Carbon $startDate, Carbon $endDate): float
    {
        return (float) collect(CarbonPeriod::create($startDate, $endDate))
            ->filter(fn (Carbon $date) => $this->isWorkingDate($date))
            ->count();
    }

    public function isWorkingDate(Carbon $date): bool
    {
        return in_array($date->format('l'), $this->activeWorkDayNames(), true)
            && ! in_array($date->toDateString(), $this->nonWorkDayDates(), true);
    }

    private function activeWorkDayNames(): array
    {
        return $this->activeWorkDayNames ??= WorkDay::query()
            ->where('active', true)
            ->pluck('day')
            ->all();
    }

    private function nonWorkDayDates(): array
    {
        return $this->nonWorkDayDates ??= NonWorkDay::query()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }
}
