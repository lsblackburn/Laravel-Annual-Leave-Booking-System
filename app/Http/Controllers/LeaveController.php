<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\User;

class LeaveController extends Controller
{
    public function view()
    {
        $leaveRequests = Leave::where('user_id', Auth::id())->orderBy('start_date', 'asc')->paginate(30);

        return view('leave.view', compact('leaveRequests'));
    }

    public function edit(Leave $request)
    {
        if ($request->user_id !== Auth::id()) {
            abort(403, 'Unauthorised action.');
        }

        if ($request->status !== 'pending') {
            return redirect()->route('leave.view')->with('error', 'Only pending leave requests can be modified.');
        }

        return view('leave.edit', compact('request'));
    }

    public function form()
    {
        return view('leave.form');
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'start_date' => $this->leaveStartDateRules(),
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'reason' => 'required|string|max:255',
            'additional_info' => 'nullable|string|max:255',
        ]);

        $validated['start_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['start_date'])->format('Y-m-d');
        $validated['end_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['end_date'])->format('Y-m-d');
        $validated['user_id'] = Auth::id();
        $validated['is_half_day'] = $request->boolean('is_half_day');

        if ($validated['is_half_day'] && !($validated['start_date'] === $validated['end_date'])) {
            return redirect()->route('leave.view')->with('error', 'A half day must have the start date equal to the end date');
        }

        $this->ensureLeaveRequestFitsAllowance($validated);

        Leave::create($validated); 

        return redirect()->route('leave.view')->with('success', 'Leave request created successfully.');
    }

    public function update(Request $request, Leave $leaveRequest)
    {
        if ($leaveRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorised action.');
        }

        if ($leaveRequest->status !== 'pending') {
            return redirect()->route('leave.view')->with('error', 'Only pending leave requests can be modified.');
        }

        $validated = $request->validate([
            'start_date' => $this->leaveStartDateRules(),
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'reason' => 'required|string|max:255',
            'additional_info' => 'nullable|string|max:255',
        ]);

        $validated['start_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['start_date'])->format('Y-m-d');
        $validated['end_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['end_date'])->format('Y-m-d');
        $validated['is_half_day'] = $request->boolean('is_half_day');
        $validated['user_id'] = Auth::id();

        if ($validated['is_half_day'] && !($validated['start_date'] === $validated['end_date'])) {
            return redirect()->route('leave.view')->with('error', 'A half day must have the start date equal to the end date');
        }

        $this->ensureLeaveRequestFitsAllowance($validated, $leaveRequest);

        $leaveRequest->update($validated);

        return redirect()->route('leave.view')->with('success', 'Leave request updated successfully.');
    }

    public function leave_response(Request $request, $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->input('response') !== 'approved' && $request->input('response') !== 'rejected') {
            return redirect()->route('admin.leave-requests')->with('error', 'Invalid response value.');
        }

        $leaveRequest = Leave::findOrFail($id);

        $validated = $request->validate([
            'manager_comment' => 'nullable',
        ]);

        if ($leaveRequest->status !== 'pending') {
            return redirect()->route('admin.leave-requests')->with('error', 'This leave request has already been processed.');
        }

        if ($request->input('response') === 'approved') {
            try {
                $this->ensureLeaveRequestFitsAllowance(
                    $this->leaveRequestData($leaveRequest),
                    $leaveRequest,
                    $leaveRequest->user,
                    ['approved']
                );
            } catch (ValidationException) {
                return redirect()->route('admin.leave-requests')->with('error', 'This leave request would exceed the employee\'s remaining allowance.');
            }
        }

        $leaveRequest->manager_comment = $validated['manager_comment'] ?? null;
        $leaveRequest->status = $request->input('response');
        $leaveRequest->save();

        return redirect()->route('admin.leave-requests')->with('success', "Leave request {$request->input('response')} successfully.");
    }

    public function calendar_events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(?:$|[T\s])/', 'before_or_equal:end'],
            'end'   => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(?:$|[T\s])/', 'after_or_equal:start'],
        ]);

        $startDate = $this->calendarRequestDate($validated['start'] ?? null);
        $endDate = $this->calendarRequestDate($validated['end'] ?? null);

        $query = Leave::with('user')
            ->where('status', 'approved');

        // Only return events that overlap with the requested date range in the calendar
        if ($startDate !== null) {
            $query->where('end_date', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->where('start_date', '<', $endDate);
        }

        $leaves = $query->get();

        $events = $leaves->map(function ($leave) {
            return [
                'title' => $leave->user->name . ' - Annual Leave' . ($leave->is_half_day ? '(Half Day)' : ''),
                'start' => $leave->start_date,
                'end' => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                'allDay' => true,
                'backgroundColor' => $leave->user->colour,
            ];
        });

        return response()->json($events);
    }

    private function calendarRequestDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }

    private function leaveStartDateRules(): array
    {
        return [
            'required',
            'date_format:d-m-Y',
            function ($attribute, $value, $fail) {
                try {
                    $startDate = Carbon::createFromFormat('d-m-Y', $value)->startOfDay();
                } catch (\Throwable) {
                    return;
                }

                if ($startDate->lt(now()->startOfDay())) {
                    $fail('The start date must be today or a future date.');
                }
            },
        ];
    }

    private function ensureLeaveRequestFitsAllowance(
        array $leaveRequest,
        ?Leave $ignoredLeave = null,
        ?User $user = null,
        array $reservedStatuses = ['approved', 'pending']
    ): void
    {
        $settings = LeaveSetting::first();
        $user ??= Auth::user();

        if (! $settings?->leave_refresh_day || ! $settings?->leave_refresh_month) {
            $requestedDays = $this->requestedLeaveDays($leaveRequest);
            $remainingAllowance = $this->remainingLeaveAllowanceWithoutRefreshSettings($user, $reservedStatuses, $ignoredLeave);

            if ($requestedDays <= $remainingAllowance) {
                return;
            }

            throw ValidationException::withMessages([
                'end_date' => "This request uses {$requestedDays} days, but you only have {$remainingAllowance} days remaining.",
            ]);
        }

        $requestStart = Carbon::parse($leaveRequest['start_date'])->startOfDay();
        $requestEnd = Carbon::parse($leaveRequest['end_date'])->startOfDay();
        $allowanceYearStart = $this->leaveAllowanceYearStart($settings, $requestStart);

        while ($allowanceYearStart->lte($requestEnd)) {
            $allowanceYearEnd = $this->leaveRefreshDateForYear($settings, (int) $allowanceYearStart->year + 1);
            $requestedDays = $this->requestedLeaveDaysWithinWindow(
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

    private function remainingLeaveAllowanceWithoutRefreshSettings(User $user, array $reservedStatuses, ?Leave $ignoredLeave): float
    {
        $usedDaysQuery = $user->leaves()->whereIn('status', $reservedStatuses);

        if ($ignoredLeave) {
            $usedDaysQuery->where('id', '!=', $ignoredLeave->id);
        }

        $usedDays = $usedDaysQuery
            ->get()
            ->sum(fn (Leave $leave) => $this->requestedLeaveDays($this->leaveRequestData($leave)));

        return (float) $user->leave_allowance - $usedDays;
    }

    private function remainingLeaveAllowanceForWindow(
        User $user,
        LeaveSetting $settings,
        Carbon $allowanceYearStart,
        Carbon $allowanceYearEnd,
        array $reservedStatuses,
        ?Leave $ignoredLeave
    ): float
    {
        $allowance = $this->leaveAllowanceForDate($user, $settings, $allowanceYearStart);
        $usedDaysQuery = $user->leaves()
            ->whereIn('status', $reservedStatuses)
            ->where('end_date', '>=', $allowanceYearStart->toDateString())
            ->where('start_date', '<', $allowanceYearEnd->toDateString());

        if ($ignoredLeave) {
            $usedDaysQuery->where('id', '!=', $ignoredLeave->id);
        }

        $usedDays = $usedDaysQuery
            ->get()
            ->sum(function (Leave $leave) use ($allowanceYearStart, $allowanceYearEnd) {
                return $this->requestedLeaveDaysWithinWindow(
                    $this->leaveRequestData($leave),
                    $allowanceYearStart,
                    $allowanceYearEnd
                );
            });

        return $allowance - $usedDays;
    }

    private function leaveRequestData(Leave $leave): array
    {
        return [
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'is_half_day' => (bool) $leave->is_half_day,
        ];
    }

    private function leaveAllowanceForDate(User $user, LeaveSetting $settings, Carbon $date): float
    {
        if ($date->isSameDay($this->leaveAllowanceYearStart($settings, Carbon::now()))) {
            return (float) $user->leave_allowance;
        }

        if (! $settings->base_allowance || ! $user->employment_start_date) {
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

    private function leaveAllowanceYearStart(LeaveSetting $settings, Carbon $date): Carbon
    {
        $refreshDate = $this->leaveRefreshDateForYear($settings, (int) $date->year);

        if ($date->lt($refreshDate)) {
            return $this->leaveRefreshDateForYear($settings, (int) $date->year - 1);
        }

        return $refreshDate;
    }

    private function leaveRefreshDateForYear(LeaveSetting $settings, int $year): Carbon
    {
        $month = (int) $settings->leave_refresh_month;
        $day = min((int) $settings->leave_refresh_day, Carbon::create($year, $month, 1)->daysInMonth);

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    private function requestedLeaveDaysWithinWindow(array $leaveRequest, Carbon $windowStart, Carbon $windowEnd): float
    {
        $requestStart = Carbon::parse($leaveRequest['start_date'])->startOfDay();
        $requestEnd = Carbon::parse($leaveRequest['end_date'])->startOfDay();

        if ($requestEnd->lt($windowStart) || $requestStart->gte($windowEnd)) {
            return 0.0;
        }

        if ($leaveRequest['is_half_day']) {
            return 0.5;
        }

        if ($requestStart->lt($windowStart)) {
            $requestStart = $windowStart->copy();
        }

        if ($requestEnd->gte($windowEnd)) {
            $requestEnd = $windowEnd->copy()->subDay();
        }

        return (float) ($requestStart->diffInDays($requestEnd) + 1);
    }

    private function requestedLeaveDays(array $leaveRequest): float
    {
        if ($leaveRequest['is_half_day']) {
            return 0.5;
        }

        $startDate = Carbon::parse($leaveRequest['start_date']);
        $endDate = Carbon::parse($leaveRequest['end_date']);

        return (float) ($startDate->diffInDays($endDate) + 1);
    }

    public function delete(Leave $leave)
    {
        if ($leave->user_id !== Auth::id()) {
            abort(403, 'Unauthorised action.');
        }

        if ($leave->status !== 'pending') {
            return redirect()->route('leave.view')->with('error', 'Only pending leave requests can be cancelled.');
        }

        $leave->delete();

        return redirect()->route('leave.view')->with('success', 'Leave request cancelled successfully.');
    }

    public function update_leave_refresh(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorised action.');
        }

        $validated = $request->validate([
            'leave_refresh_month' => ['required', 'integer', 'min:1', 'max:12'],
            'leave_refresh_day' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {

                    $month = (int) $request->leave_refresh_month;

                    // Using a leap year so February can support 29
                    $daysInMonth = Carbon::create(2024, $month, 1)->daysInMonth;

                    if ($value > $daysInMonth) {
                        $fail("The selected month only has {$daysInMonth} days.");
                    }
                },
            ],
        ]);

        $leave = LeaveSetting::firstOrFail();

        $validated['leave_refresh_month'] = (int) $validated['leave_refresh_month'];
        $validated['leave_refresh_day'] = (int) $validated['leave_refresh_day'];

        $leave->update($validated);

        return redirect()->route('admin.view-leave-rules')->with('success', 'Leave refresh dates have updated successfully.');
    }

    public function update_leave_allowance(Request $request, LeaveSetting $leave)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorised action.');
        }

        $validated = $request->validate([
            'base_allowance' => ['required', 'numeric', 'min:1'],
            'increase_after_years' => ['required', 'integer', 'min:0'],
            'increase_by_days' => ['required', 'numeric', 'min:0'],
            'maximum_allowance' => ['required', 'numeric', 'min:1', 'gte:base_allowance'],
        ]);

        $leave = LeaveSetting::firstOrFail();

        $validated['base_allowance'] = (float) $validated['base_allowance'];
        $validated['increase_after_years'] = (int) $validated['increase_after_years'];
        $validated['increase_by_days'] = (float) $validated['increase_by_days'];
        $validated['maximum_allowance'] = (float) $validated['maximum_allowance'];

        $leave->update($validated);

        return redirect()->route('admin.view-leave-rules')->with('success', 'Leave allowance settings have updated successfully.');
    }

}
