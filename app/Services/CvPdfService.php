<?php

namespace App\Services;

use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;

class CvPdfService
{
    public function generate(Employee $employee)
    {
        $employee->loadMissing([
            'department',
            'currentService',
            'sector',
            'tradeBody',
            'qualification',
            'jobTitle',
            'diplomas' => fn($q) => $q->orderBy('year_obtained', 'desc'),
            'contracts',
        ]);

        $recruitmentDiploma = $employee->diplomas->firstWhere('type', 'recruitment_diploma');
        $highestDiploma = $employee->diplomas->firstWhere('type', 'highest_diploma');
        $trainings = $employee->diplomas->where('type', 'training')->sortByDesc('year_obtained');

        $pdf = Pdf::loadView('pdf.employee-cv', [
            'employee' => $employee,
            'recruitmentDiploma' => $recruitmentDiploma,
            'highestDiploma' => $highestDiploma,
            'trainings' => $trainings,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function download(Employee $employee)
    {
        $filename = 'CV_' . str_replace(' ', '_', $employee->full_name) . '_' . $employee->matricule . '.pdf';

        return $this->generate($employee)->download($filename);
    }
}
