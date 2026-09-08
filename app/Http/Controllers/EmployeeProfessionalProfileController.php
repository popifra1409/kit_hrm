<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ProfessionalProfileService;
use Illuminate\Http\Request;

class EmployeeProfessionalProfileController extends Controller
{
    public function download(Request $request, Employee $employee)
    {
        abort_unless(
            $request->user() && $request->user()->hasAnyRole(['super_admin', 'admin', 'drh']),
            403
        );

        $service = new ProfessionalProfileService();

        return $service->download($employee);
    }

    public function preview(Request $request, Employee $employee)
    {
        abort_unless(
            $request->user() && $request->user()->hasAnyRole(['super_admin', 'admin', 'drh']),
            403
        );

        $employee->loadMissing([
            'department',
            'currentService',
            'sector',
            'tradeBody',
            'qualification',
            'jobTitle',
            'diplomas',
            'assignmentHistory',
            'advancementHistory',
        ]);

        $recruitmentDiploma = $employee->diplomas->firstWhere('type', 'recruitment_diploma');
        $highestDiploma = $employee->diplomas->firstWhere('type', 'highest_diploma');
        $trainings = $employee->diplomas->where('type', 'training')->sortByDesc('year_obtained');
        $careerPath = $employee->assignmentHistory->sortBy('effective_date')->values();
        $advancements = $employee->advancementHistory->sortBy('effective_date')->values();
        $servicesWorked = $careerPath->pluck('new_service_name')->filter()->unique()->values();

        return view('pdf.employee-professional-profile', [
            'employee' => $employee,
            'recruitmentDiploma' => $recruitmentDiploma,
            'highestDiploma' => $highestDiploma,
            'trainings' => $trainings,
            'careerPath' => $careerPath,
            'advancements' => $advancements,
            'servicesWorked' => $servicesWorked,
        ]);
    }
}
