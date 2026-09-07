<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\CvPdfService;
use Illuminate\Http\Request;

class EmployeeCvController extends Controller
{
    public function download(Request $request, Employee $employee)
    {
        abort_unless(
            $request->user() && $request->user()->hasAnyRole(['super_admin', 'admin', 'drh']),
            403
        );

        $service = new CvPdfService();

        return $service->download($employee);
    }

    /**
     * Aperçu HTML du CV avant génération du PDF.
     */
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
        ]);

        $recruitmentDiploma = $employee->diplomas->firstWhere('type', 'recruitment_diploma');
        $highestDiploma = $employee->diplomas->firstWhere('type', 'highest_diploma');
        $trainings = $employee->diplomas->where('type', 'training')->sortByDesc('year_obtained');

        return view('pdf.employee-cv', [
            'employee' => $employee,
            'recruitmentDiploma' => $recruitmentDiploma,
            'highestDiploma' => $highestDiploma,
            'trainings' => $trainings,
        ]);
    }
}
