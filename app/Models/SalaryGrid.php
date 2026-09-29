<?php

namespace App\Models;

use App\Support\CameroonCivilServiceGrid;
use Illuminate\Database\Eloquent\Model;

class SalaryGrid extends Model
{
    protected $fillable = [
        'classification_type',
        'category',
        'echelon',
        'indice',
        'salaire_indiciaire_brut',
        'complement_forfaitaire',
        'indemnite_logement',
        'base_salary',
        'effective_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'indice' => 'integer',
        // ❌ N'PAS caster category et echelon en integer
        // ✅ Les laisser comme string/varchar (les fonctionnaires utilisent des codes comme "A1", "2/3", "CL Exc")
    ];

    protected static function booted(): void
    {
        static::saving(function (SalaryGrid $grid) {
            // Contractuels : l'indice est retrouvé par correspondance de salaire dans la
            // grille officielle des fonctionnaires (indice au salaire de base le plus proche).
            // Recalculé si l'indice n'est pas encore connu, ou si le salaire de base vient de changer.
            if ($grid->classification_type === 'numeric' && $grid->base_salary !== null) {
                if ($grid->indice === null || $grid->isDirty('base_salary')) {
                    $grid->indice = CameroonCivilServiceGrid::nearestIndiceForSalary((float) $grid->base_salary);
                }
            }

            // Fonctionnaires : on complète ce qui manque à partir de la grille officielle,
            // sans jamais écraser une valeur saisie manuellement.
            if ($grid->classification_type === 'cameroon') {
                $official = CameroonCivilServiceGrid::find((string) $grid->category, (string) $grid->echelon);

                if ($official) {
                    foreach (['indice', 'salaire_indiciaire_brut', 'complement_forfaitaire', 'indemnite_logement'] as $field) {
                        if ($grid->{$field} === null) {
                            $grid->{$field} = $official[$field];
                        }
                    }
                }

                // Le salaire de base d'un fonctionnaire = son salaire indiciaire brut.
                if ($grid->base_salary === null && $grid->salaire_indiciaire_brut !== null) {
                    $grid->base_salary = $grid->salaire_indiciaire_brut;
                }
            }
        });
    }

    /**
     * Total brut d'un fonctionnaire : (1) salaire indiciaire + (2) complément forfaitaire + (3) indemnité de logement.
     */
    public function getTotalSalaryAttribute(): ?float
    {
        if ($this->salaire_indiciaire_brut === null) {
            return null;
        }

        return (float) $this->salaire_indiciaire_brut
            + (float) $this->complement_forfaitaire
            + (float) $this->indemnite_logement;
    }

    /**
     * Ligne de grille active la plus récente pour un type/catégorie/échelon.
     */
    public static function lookup(string $classificationType, $category, $echelon): ?self
    {
        if ($category === null || $category === '' || $echelon === null || $echelon === '') {
            return null;
        }

        return self::where('classification_type', $classificationType)
            ->where('category', (string) $category)
            ->where('echelon', (string) $echelon)
            ->where('is_active', true)
            ->latest('effective_date')
            ->first();
    }

    /**
     * Indice correspondant à une classification (utilisé pour l'auto-remplissage du
     * formulaire employé). Toujours une simple lecture : l'indice est stocké sur la
     * ligne de grille elle-même, qu'il vienne de la grille officielle (fonctionnaires)
     * ou de la correspondance par salaire (contractuels).
     */
    public static function lookupIndice(string $classificationType, $category, $echelon): ?int
    {
        $grid = self::lookup($classificationType, $category, $echelon);

        return $grid?->indice !== null ? (int) $grid->indice : null;
    }

    /**
     * Récupérer le salaire de base
     */
    public static function getBaseSalary($category, $echelon, ?string $classificationType = null): float
    {
        $query = self::where('category', (string) $category)
            ->where('echelon', (string) $echelon)
            ->where('is_active', true);

        if ($classificationType) {
            $query->where('classification_type', $classificationType);
        }

        $salary = $query->latest('effective_date')->first();

        if (!$salary) {
            throw new \Exception("Grille salariale non trouvée pour {$category}/{$echelon}");
        }

        return (float) $salary->base_salary;
    }
}
