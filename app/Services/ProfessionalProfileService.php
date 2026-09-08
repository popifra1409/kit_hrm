<?php

namespace App\Services;

use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;

class ProfessionalProfileService
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
            'assignmentHistory',
            'advancementHistory',
        ]);

        $recruitmentDiploma = $employee->diplomas->firstWhere('type', 'recruitment_diploma');
        $highestDiploma = $employee->diplomas->firstWhere('type', 'highest_diploma');
        $trainings = $employee->diplomas->where('type', 'training')->sortByDesc('year_obtained');

        $careerPath = $employee->assignmentHistory->sortBy('effective_date')->values();
        $advancements = $employee->advancementHistory->sortBy('effective_date')->values();

        $servicesWorked = $careerPath
            ->pluck('new_service_name')
            ->filter()
            ->unique()
            ->values();

        $pdf = Pdf::loadView('pdf.employee-professional-profile', [
            'employee' => $employee,
            'recruitmentDiploma' => $recruitmentDiploma,
            'highestDiploma' => $highestDiploma,
            'trainings' => $trainings,
            'careerPath' => $careerPath,
            'advancements' => $advancements,
            'servicesWorked' => $servicesWorked,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function download(Employee $employee)
    {
        $filename = 'Profil_' . str_replace(' ', '_', $employee->full_name) . '_' . $employee->matricule . '.pdf';

        return $this->generate($employee)->download($filename);
    }
}
