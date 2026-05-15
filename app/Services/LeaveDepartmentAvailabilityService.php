<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class LeaveDepartmentAvailabilityService
{
    public function ensureDepartmentHasCoverage(
        User $user,
        array $leaveRequest,
        ?Leave $ignoredLeave = null,
        array $reservedStatuses = ['approved', 'pending']
    ): void {
        if (! $user->department_id) {
            return;
        }

        $departmentUserIds = User::where('department_id', $user->department_id)->pluck('id');

        if ($departmentUserIds->count() <= 1) {
            return;
        }

        $requestStart = Carbon::parse($leaveRequest['start_date'])->startOfDay();
        $requestEnd = Carbon::parse($leaveRequest['end_date'])->startOfDay();

        $overlappingLeavesQuery = Leave::query()
            ->whereIn('user_id', $departmentUserIds)
            ->whereIn('status', $reservedStatuses)
            ->where('end_date', '>=', $requestStart->toDateString())
            ->where('start_date', '<=', $requestEnd->toDateString());

        if ($ignoredLeave) {
            $overlappingLeavesQuery->where('id', '!=', $ignoredLeave->id);
        }

        $overlappingLeaves = $overlappingLeavesQuery->get();

        foreach (CarbonPeriod::create($requestStart, $requestEnd) as $date) {
            $unavailableUserIds = $overlappingLeaves
                ->filter(function (Leave $leave) use ($date) {
                    return Carbon::parse($leave->start_date)->startOfDay()->lte($date)
                        && Carbon::parse($leave->end_date)->startOfDay()->gte($date);
                })
                ->pluck('user_id')
                ->push($user->id)
                ->unique();

            if ($unavailableUserIds->count() >= $departmentUserIds->count()) {
                throw ValidationException::withMessages([
                    'end_date' => 'This leave would leave nobody available in your department on '.$date->format('d/m/Y').'.',
                ]);
            }
        }
    }
}
