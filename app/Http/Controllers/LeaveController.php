<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

use App\Models\Leave;

class LeaveController extends Controller
{
    public function view()
    {
        $leaveRequests = Leave::where('user_id', Auth::id())->orderBy('start_date', 'asc')->get();

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
            'start_date' => 'required|date_format:d-m-Y',
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'reason' => 'required|string|max:255',
            'additional_info' => 'nullable|string|max:255',
        ]);

        $validated['start_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['start_date'])->format('Y-m-d');
        $validated['end_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['end_date'])->format('Y-m-d');
        $validated['user_id'] = Auth::id();
        $validated['is_half_day'] = $request->boolean('is_half_day');

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
            'start_date' => 'required|date_format:d-m-Y',
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'reason' => 'required|string|max:255',
            'additional_info' => 'nullable|string|max:255',
        ]);

        $validated['start_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['start_date'])->format('Y-m-d');
        $validated['end_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['end_date'])->format('Y-m-d');
        $validated['is_half_day'] = $request->boolean('is_half_day');
        $validated['user_id'] = Auth::id();

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

        if ($leaveRequest->status !== 'pending') {
            return redirect()->route('admin.leave-requests')->with('error', 'This leave request has already been processed.');
        }

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
                'backgroundColor' => 'var(--color-success)',
                'borderColor' => 'var(--color-success)',
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

}
