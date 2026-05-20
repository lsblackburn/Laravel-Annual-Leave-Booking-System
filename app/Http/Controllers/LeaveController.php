<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\NonWorkDay;
use App\Models\User;
use App\Models\WorkDay;
use App\Notifications\LeaveRequestResponded;
use App\Notifications\LeaveRequestRespondedEmail;
use App\Notifications\LeaveRequestSubmitted;
use App\Notifications\LeaveRequestSubmittedEmail;
use App\Services\LeaveAllowanceService;
use App\Services\LeaveDepartmentAvailabilityService;

class LeaveController extends Controller
{
    public function __construct(private LeaveAllowanceService $leaveAllowance, private LeaveDepartmentAvailabilityService $departmentAvailability
    ) {
    }

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

        $this->leaveAllowance->ensureLeaveRequestFitsAllowance(Auth::user(), $validated);
        $this->departmentAvailability->ensureDepartmentHasCoverage(Auth::user(), $validated);

        $leave = Leave::create($validated);

        // Send in-app notifications immediately and queue email delivery through a separate notification class.
        User::query()
            ->where('role', 'admin')
            ->get()
            ->each(function (User $admin) use ($leave) {
                $admin->notify(new LeaveRequestSubmitted($leave));
                $admin->notify(new LeaveRequestSubmittedEmail($leave));
            });

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

        $this->leaveAllowance->ensureLeaveRequestFitsAllowance(Auth::user(), $validated, $leaveRequest);
        $this->departmentAvailability->ensureDepartmentHasCoverage(Auth::user(), $validated, $leaveRequest);

        $leaveRequest->update($validated);

        return redirect()->route('leave.view')->with('success', 'Leave request updated successfully.');
    }

    public function leaveResponse(Request $request, $id)
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
                // Re-check approval using approved leave only; pending requests should not block the request being approved.
                $this->leaveAllowance->ensureLeaveRequestFitsAllowance(
                    $leaveRequest->user,
                    $this->leaveAllowance->leaveRequestData($leaveRequest),
                    $leaveRequest,
                    ['approved']
                );
            } catch (ValidationException) {
                return redirect()->route('admin.leave-requests')->with('error', 'This leave request would exceed the employee\'s remaining allowance.');
            }

            try {
                // Department coverage can change while a request is pending, so validate again at approval time.
                $this->departmentAvailability->ensureDepartmentHasCoverage(
                    $leaveRequest->user,
                    $this->leaveAllowance->leaveRequestData($leaveRequest),
                    $leaveRequest,
                    ['approved']
                );
            } catch (ValidationException) {
                return redirect()->route('admin.leave-requests')->with('error', 'This leave request would leave the employee\'s department without cover.');
            }
        }

        $leaveRequest->manager_comment = $validated['manager_comment'] ?? null;
        $leaveRequest->status = $request->input('response');
        $leaveRequest->save();
        // Keep the database notification synchronous, but send the email variant through the queue.
        $leaveRequest->user->notify(new LeaveRequestResponded($leaveRequest));
        $leaveRequest->user->notify(new LeaveRequestRespondedEmail($leaveRequest));

        return redirect()->route('admin.leave-requests')->with('success', "Leave request {$request->input('response')} successfully.");
    }

    public function calendarEvents(Request $request): JsonResponse
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

        $nonWorkDayQuery = NonWorkDay::query();

        if ($startDate !== null) {
            $nonWorkDayQuery->whereDate('date', '>=', $startDate);
        }

        if ($endDate !== null) {
            $nonWorkDayQuery->whereDate('date', '<', $endDate);
        }

        $leaves = $query->get();
        $nonWorkDays = $nonWorkDayQuery->get();

        $events = $leaves->map(function ($leave) {
            return [
                'title' => $leave->user->name . ' - Annual Leave' . ($leave->is_half_day ? '(Half Day)' : ''),
                'start' => $leave->start_date,
                'end' => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                'allDay' => true,
                'backgroundColor' => $leave->user->colour,
            ];
        })->toBase();

        $nonWorkDayEvents = $nonWorkDays->map(function ($nonWorkDay) {
            return [
                'title' => $nonWorkDay->name . ' - Non-work day',
                'start' => $nonWorkDay->date,
                'end' => Carbon::parse($nonWorkDay->date)->addDay()->toDateString(),
                'allDay' => true,
                'backgroundColor' => '#6b7280',
                'borderColor' => '#4b5563',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'non_work_day',
                ],
            ];
        })->toBase();

        $events = $events->merge($nonWorkDayEvents)->values();

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

    public function updateLeaveRefresh(Request $request)
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

    public function updateLeaveAllowance(Request $request, LeaveSetting $leave)
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

    public function updateWorkDays(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorised action.');
        }

        $validated = $request->validate([
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'exists:work_days,id'],
        ]);

        $activeWorkDayIds = collect($validated['work_days'])->map(fn ($id) => (int) $id);

        WorkDay::query()->update(['active' => false]);
        WorkDay::query()
            ->whereIn('id', $activeWorkDayIds)
            ->update(['active' => true]);

        return redirect()->route('admin.view-leave-rules')->with('success', 'Working days updated successfully.');
    }

}
