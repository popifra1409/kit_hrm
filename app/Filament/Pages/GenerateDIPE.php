<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Payroll;
use App\Models\DIPESubmission;
use Illuminate\Support\Facades\Storage;

class GenerateDIPE extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Générer DIPE CNPS';
    protected static ?string $title = 'Génération du DIPE CNPS';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.generate-dipe';

    public $month;
    public $year;
    public $type = 'mensuel';

    // Constantes CHUY (à adapter selon vos données réelles)
    const NUMERO_EMPLOYEUR = '3210100565'; // 10 caractères
    const CLE_EMPLOYEUR = 'S'; // 1 caractère
    const NUMERO_CONTRIBUABLE = 'M062900000000'; // 14 caractères (à vérifier)
    const REGIME_CNPS = '1'; // Régime général

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('type')
                ->label('Type de DIPE')
                ->options([
                    'mensuel' => 'DIPE Mensuel',
                    'debut_exercice' => 'DIPE de Début d\'Exercice',
                    'fin_exercice' => 'DIPE de Fin d\'Exercice',
                ])
                ->required()
                ->reactive()
                ->native(false)
                ->default('mensuel'),

            Select::make('month')
                ->label('Mois')
                ->options([
                    1 => 'Janvier',
                    2 => 'Février',
                    3 => 'Mars',
                    4 => 'Avril',
                    5 => 'Mai',
                    6 => 'Juin',
                    7 => 'Juillet',
                    8 => 'Août',
                    9 => 'Septembre',
                    10 => 'Octobre',
                    11 => 'Novembre',
                    12 => 'Décembre'
                ])
                ->required()
                ->native(false)
                ->default(now()->month)
                ->visible(fn($get) => $get('type') === 'mensuel'),

            Select::make('year')
                ->label('Année')
                ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                ->required()
                ->native(false)
                ->default(now()->year),
        ];
    }

    public function generate()
    {
        $this->validate();

        try {
            if ($this->type === 'mensuel') {
                return $this->generateDIPEMensuel();
            } elseif ($this->type === 'debut_exercice') {
                return $this->generateDIPEDebutExercice();
            } else {
                return $this->generateDIPEFinExercice();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur lors de la génération : ' . $e->getMessage())
                ->send();
        }
    }

    protected function generateDIPEMensuel()
    {
        // Récupérer les bulletins du mois
        $payrolls = Payroll::with('employee')
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($payrolls->isEmpty()) {
            Notification::make()
                ->title('Aucun bulletin')
                ->warning()
                ->body('Aucun bulletin de paie trouvé pour cette période')
                ->send();
            return;
        }

        // Générer le numéro DIPE
        $numeroDIPE = str_pad(DIPESubmission::where('year', $this->year)->count() + 1, 5, '0', STR_PAD_LEFT);
        $cleDIPE = $this->calculateCheckDigit($numeroDIPE);

        // Créer le fichier DIPE au format texte
        $lines = [];
        $numeroLigne = 1;

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;

            // Vérifier que l'employé a un numéro CNPS
            if (!$employee->cnps_number) {
                continue;
            }

            $line = $this->formatDIPEMensuelLine(
                $numeroDIPE,
                $cleDIPE,
                $this->month,
                $employee,
                $payroll,
                $numeroLigne
            );

            $lines[] = $line;
            $numeroLigne++;

            if ($numeroLigne > 16) {
                $numeroLigne = 1; // Reset après 16 lignes
            }
        }

        // Sauvegarder le fichier
        $filename = 'DIPE_' . $this->month . '_' . $this->year . '.txt';
        $filepath = 'dipe/' . $filename;
        Storage::put($filepath, implode("\r\n", $lines));

        // Enregistrer dans la base
        DIPESubmission::create([
            'numero_dipe' => $numeroDIPE,
            'cle_numero_dipe' => $cleDIPE,
            'numero_contribuable' => self::NUMERO_CONTRIBUABLE,
            'month' => $this->month,
            'year' => $this->year,
            'type' => 'mensuel',
            'regime_cnps' => self::REGIME_CNPS,
            'total_employees' => $payrolls->count(),
            'total_salaire_brut' => $payrolls->sum('gross_salary'),
            'total_salaire_cotisable' => $payrolls->sum('gross_cnps'),
            'total_cotisations_cnps' => $payrolls->sum('cnps_employee'),
            'total_irpp' => $payrolls->sum('irpp'),
            'file_path' => $filepath,
            'status' => 'validated',
        ]);

        Notification::make()
            ->title('DIPE généré !')
            ->success()
            ->body("{$payrolls->count()} lignes générées. Fichier : {$filename}")
            ->send();

        return Storage::download($filepath);
    }

    protected function formatDIPEMensuelLine($numeroDIPE, $cleDIPE, $month, $employee, $payroll, $numeroLigne)
    {
        // Format selon spécifications CNPS (135 caractères)
        $line = '';

        // CODE ENREGISTREMENT (3) : C04
        $line .= 'C04';

        // NUMERO DIPE (5)
        $line .= str_pad($numeroDIPE, 5, '0', STR_PAD_LEFT);

        // CLE NUMERO DIPE (1)
        $line .= $cleDIPE;

        // NUMERO CONTRIBUABLE (14)
        $line .= str_pad(self::NUMERO_CONTRIBUABLE, 14, ' ', STR_PAD_RIGHT);

        // NUMERO DE FEUILLET (2) : mois sur 2 caractères
        $line .= str_pad($month, 2, '0', STR_PAD_LEFT);

        // NUMERO EMPLOYEUR (10)
        $line .= str_pad(self::NUMERO_EMPLOYEUR, 10, '0', STR_PAD_LEFT);

        // CLE NUMERO EMPLOYEUR (1)
        $line .= self::CLE_EMPLOYEUR;

        // REGIME CNPS (1)
        $line .= self::REGIME_CNPS;

        // ANNEE DU DIPE (4)
        $line .= $this->year;

        // NUMERO ASSURE (10) - enlever les tirets
        $cnpsNumber = str_replace('-', '', $employee->cnps_number);
        $line .= str_pad(substr($cnpsNumber, 0, 10), 10, '0', STR_PAD_LEFT);

        // CLE NUMERO ASSURE (1)
        $line .= substr($cnpsNumber, -1, 1);

        // NOMBRE DE JOURS (2)
        $line .= '30'; // Par défaut 30 jours

        // SALAIRE BRUT (10)
        $line .= str_pad((int)$payroll->gross_salary, 10, '0', STR_PAD_LEFT);

        // SALAIRE EXCEPTIONNEL (10)
        $line .= str_pad('0', 10, '0', STR_PAD_LEFT);

        // SALAIRE TAXABLE (10)
        $line .= str_pad((int)$payroll->gross_taxable, 10, '0', STR_PAD_LEFT);

        // SALAIRE COTISABLE CNPS (10)
        $line .= str_pad((int)$payroll->gross_cnps, 10, '0', STR_PAD_LEFT);

        // SALAIRE COTISABLE PLAFONNE (10) - Plafond CNPS 750000
        $plafond = min($payroll->gross_cnps, 750000);
        $line .= str_pad((int)$plafond, 10, '0', STR_PAD_LEFT);

        // RETENUE IRPP (8)
        $line .= str_pad((int)$payroll->irpp, 8, '0', STR_PAD_LEFT);

        // RETENUE TAXE COMMUNALE (6)
        $taxeCommunale = $payroll->lines()
            ->whereHas('payrollItem', fn($q) => $q->where('code', 'TDL'))
            ->sum('amount');
        $line .= str_pad((int)$taxeCommunale, 6, '0', STR_PAD_LEFT);

        // NUMERO DE LIGNE (2)
        $line .= str_pad($numeroLigne, 2, '0', STR_PAD_LEFT);

        // MATRICULE INTERNE (14)
        $line .= str_pad($employee->matricule_interne ?? $employee->matricule, 14, ' ', STR_PAD_RIGHT);

        // FILLER (1)
        $line .= ' ';

        return $line;
    }

    protected function calculateCheckDigit($number)
    {
        // Algorithme simple de clé (à adapter selon CNPS)
        $sum = array_sum(str_split($number));
        return chr(65 + ($sum % 26)); // A-Z
    }

    protected function generateDIPEDebutExercice()
    {
        // TODO: Implémenter
        Notification::make()
            ->title('En développement')
            ->info()
            ->body('Fonctionnalité DIPE début d\'exercice à venir')
            ->send();
    }

    protected function generateDIPEFinExercice()
    {
        // TODO: Implémenter
        Notification::make()
            ->title('En développement')
            ->info()
            ->body('Fonctionnalité DIPE fin d\'exercice à venir')
            ->send();
    }

    public function getStats()
    {
        $count = Payroll::where('month', $this->month ?? now()->month)
            ->where('year', $this->year ?? now()->year)
            ->count();

        return ['count' => $count];
    }
}
