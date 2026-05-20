<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserDepartment;

class UserDepartmentController extends Controller
{
    public function createCompanyDepartments(Request $request)
    {
        $validated = $request->validate([
            'department' => 'required|string|max:255|unique:user_departments',
        ]);

        UserDepartment::create($validated); 

        return redirect()->route('admin.view-company-departments')->with('success', 'Department created successfully.');
    }

    public function deleteCompanyDepartment(UserDepartment $department)
    {
        $department->delete();

        return redirect()->route('admin.view-company-departments')->with('success', 'Department deleted successfully.');
    }
}
