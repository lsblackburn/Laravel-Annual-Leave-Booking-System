<?php

namespace App\Http\Controllers;

use App\Models\NonWorkDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NonWorkDayController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date_format:d-m-Y',
        ], [
            'date.date_format' => 'The date must be in the format dd-mm-yyyy.',
        ]);

        $validated['date'] = Carbon::createFromFormat('d-m-Y', $validated['date'])->format('Y-m-d');

        if (NonWorkDay::whereDate('date', $validated['date'])->exists()) {
            throw ValidationException::withMessages([
                'date' => 'This date has already been added as a non-work day.',
            ]);
        }

        NonWorkDay::create($validated);

        return redirect()
            ->route('admin.view-leave-rules')
            ->with('success', 'Non-work day added successfully.');
    }

    public function destroy(NonWorkDay $nonWorkDay)
    {
        $nonWorkDay->delete();

        return redirect()
            ->route('admin.view-leave-rules')
            ->with('success', 'Non-work day deleted successfully.');
    }
}
