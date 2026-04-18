<?php

namespace App\Services;

use App\Models\EmployeeCard;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CardPdfService
{
    /**
     * Générer le PDF de la carte professionnelle
     */
    public function generateProfessionalCard(EmployeeCard $card): string
    {
        $employee = $card->employee;

        // Données pour la vue
        $data = [
            'card' => $card,
            'employee' => $employee,
            'hospital_name' => \App\Models\SystemSetting::get('hospital_name', 'Centre Hospitalier Universitaire de Yaoundé'),
            'hospital_acronym' => \App\Models\SystemSetting::get('hospital_acronym', 'CHUY'),
            'hospital_city' => \App\Models\SystemSetting::get('hospital_city', 'Yaoundé'),
            'logo_path' => $this->getLogoPath(),
            'qr_code_path' => $this->getQrCodePath($card),
            'drapeau_path' => public_path('images/official/drapeau_cameroun.png'),
            'armoiries_path' => public_path('images/official/armoiries_cameroun.png'),
            'signature_dg_path' => public_path('images/official/signature_dg.png'),
            'photo_path' => $this->getEmployeePhotoPath($employee),
        ];

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.professional-card', $data)
            ->setPaper([0, 0, 243, 153], 'landscape') // Format carte de crédit (85.6mm x 53.98mm)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        // Sauvegarder
        $filename = 'cards/professional-card-' . $card->card_number . '.pdf';
        $pdf->save(storage_path('app/public/' . $filename));

        // Mettre à jour le chemin dans la carte
        $card->card_pdf_path = $filename;
        $card->save();

        return $filename;
    }

    /**
     * Générer le PDF de la carte de santé
     */
    public function generateHealthCard(EmployeeCard $card): string
    {
        $employee = $card->employee;

        // Données pour la vue
        $data = [
            'card' => $card,
            'employee' => $employee,
            'hospital_name' => \App\Models\SystemSetting::get('hospital_name', 'Centre Hospitalier Universitaire de Yaoundé'),
            'hospital_acronym' => \App\Models\SystemSetting::get('hospital_acronym', 'CHUY'),
            'qr_code_path' => $this->getQrCodePath($card),
            'photo_path' => $this->getEmployeePhotoPath($employee),
            'coverage_rate' => 75, // Ou depuis la config
        ];

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.health-card', $data)
            ->setPaper([0, 0, 243, 153], 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        // Sauvegarder
        $filename = 'cards/health-card-' . $card->card_number . '.pdf';
        $pdf->save(storage_path('app/public/' . $filename));

        // Mettre à jour le chemin dans la carte
        $card->card_pdf_path = $filename;
        $card->save();

        return $filename;
    }

    /**
     * Obtenir le chemin du logo
     */
    protected function getLogoPath(): ?string
    {
        $logoPath = \App\Models\SystemSetting::get('logo_path');
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return storage_path('app/public/' . $logoPath);
        }
        return null;
    }

    /**
     * Obtenir le chemin du QR Code
     */
    protected function getQrCodePath(EmployeeCard $card): ?string
    {
        if ($card->qr_code_path && Storage::disk('public')->exists($card->qr_code_path)) {
            return storage_path('app/public/' . $card->qr_code_path);
        }
        return null;
    }

    /**
     * Obtenir le chemin de la photo de l'employé
     */
    protected function getEmployeePhotoPath(Employee $employee): ?string
    {
        if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
            return storage_path('app/public/' . $employee->photo);
        }
        return public_path('images/default-avatar.png');
    }
}
