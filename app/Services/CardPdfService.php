<?php

namespace App\Services;

use App\Models\EmployeeCard;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CardPdfService
{
    /**
     * Générer le PDF de la carte professionnelle.
     *
     * @param EmployeeCard $card
     * @param string $orientation 'horizontal' (format hôpital actuel) ou 'vertical' (format type CNI)
     */
    public function generateProfessionalCard(EmployeeCard $card, string $orientation = 'horizontal'): string
    {
        $employee = $card->employee;

        $data = [
            'card' => $card,
            'employee' => $employee,
            'hospital_name' => \App\Models\SystemSetting::get('hospital_name', 'Hôpital Général de Yaoundé'),
            'hospital_acronym' => \App\Models\SystemSetting::get('hospital_acronym', 'HGY'),
            'hospital_city' => \App\Models\SystemSetting::get('hospital_city', 'Yaoundé'),
            'logo_path' => $this->getLogoPath(),
            'qr_code_path' => $this->getQrCodePath($card),
            'drapeau_path' => public_path('images/official/drapeau_cameroun.png'),
            'armoiries_path' => public_path('images/official/armoiries_cameroun.png'),
            'signature_dg_path' => public_path('images/official/signature_dg.png'),
            'photo_path' => $this->getEmployeePhotoPath($employee),
        ];

        if ($orientation === 'vertical') {
            $view = 'pdf.professional-card-vertical';
            $paperSize = [0, 0, 153, 243];
            $paperOrientation = 'portrait';
        } else {
            $view = 'pdf.professional-card-horizontal';
            $paperSize = [0, 0, 243, 153];
            $paperOrientation = 'landscape';
        }

        $pdf = Pdf::loadView($view, $data)
            ->setPaper($paperSize, $paperOrientation)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = 'cards/professional-card-' . $card->card_number . '-' . $orientation . '.pdf';

        if (!Storage::disk('public')->exists('cards')) {
            Storage::disk('public')->makeDirectory('cards');
        }

        $pdf->save(storage_path('app/public/' . $filename));

        $card->card_pdf_path = $filename;
        $card->orientation = $orientation;
        $card->save();

        return $filename;
    }

    /**
     * Générer le PDF de la carte de santé
     */
    public function generateHealthCard(EmployeeCard $card): string
    {
        $employee = $card->employee;

        $data = [
            'card' => $card,
            'employee' => $employee,
            'hospital_name' => \App\Models\SystemSetting::get('hospital_name', 'Hôpital Général de Yaoundé'),
            'hospital_acronym' => \App\Models\SystemSetting::get('hospital_acronym', 'HGY'),
            'qr_code_path' => $this->getQrCodePath($card),
            'photo_path' => $this->getEmployeePhotoPath($employee),
            'coverage_rate' => $employee->effective_coverage_rate,
        ];

        $pdf = Pdf::loadView('pdf.health-card', $data)
            ->setPaper([0, 0, 243, 153], 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = 'cards/health-card-' . $card->card_number . '.pdf';

        if (!Storage::disk('public')->exists('cards')) {
            Storage::disk('public')->makeDirectory('cards');
        }

        $pdf->save(storage_path('app/public/' . $filename));

        $card->card_pdf_path = $filename;
        $card->save();

        return $filename;
    }

    protected function getLogoPath(): ?string
    {
        $logoPath = \App\Models\SystemSetting::get('logo_path');
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return storage_path('app/public/' . $logoPath);
        }
        return null;
    }

    protected function getQrCodePath(EmployeeCard $card): ?string
    {
        if ($card->qr_code_path && Storage::disk('public')->exists($card->qr_code_path)) {
            return storage_path('app/public/' . $card->qr_code_path);
        }
        return null;
    }

    protected function getEmployeePhotoPath(Employee $employee): ?string
    {
        if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
            return storage_path('app/public/' . $employee->photo);
        }
        return public_path('images/default-avatar.png');
    }
}
