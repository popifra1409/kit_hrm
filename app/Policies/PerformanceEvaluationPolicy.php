<?php

namespace App\Policies;

use App\Models\PerformanceEvaluation;
use App\Models\User;

class PerformanceEvaluationPolicy
{
    /**
     * Voir la liste des évaluations
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_evaluations');
    }

    /**
     * Voir une évaluation spécifique
     */
    public function view(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        return $user->can('view_evaluations');
    }

    /**
     * Créer une évaluation
     */
    public function create(User $user): bool
    {
        return $user->can('create_evaluations');
    }

    /**
     * Modifier une évaluation
     */
    public function update(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // Ne peut pas modifier une évaluation validée
        if ($performanceEvaluation->status === 'validated') {
            return false;
        }

        return $user->can('edit_evaluations');
    }

    /**
     * Supprimer une évaluation
     */
    public function delete(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // Ne peut pas supprimer une évaluation validée
        if ($performanceEvaluation->status === 'validated') {
            return false;
        }

        return $user->can('delete_evaluations');
    }

    /**
     * Valider une évaluation
     */
    public function validate(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // Seulement si en statut pending_validator
        if ($performanceEvaluation->status !== 'pending_validator') {
            return false;
        }

        return $user->can('validate_evaluations');
    }

    /**
     * Contester une évaluation (employé)
     */
    public function contest(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // L'employé peut contester si l'évaluation est en attente de sa signature
        if ($performanceEvaluation->status === 'pending_employee') {
            return true; // Tout utilisateur peut contester son évaluation
        }

        return false;
    }

    /**
     * Signer en tant qu'évaluateur
     */
    public function signAsEvaluator(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // L'évaluateur peut signer si l'évaluation est en brouillon
        if ($performanceEvaluation->status === 'draft' && $performanceEvaluation->evaluator_id === $user->id) {
            return true;
        }

        return $user->can('create_evaluations');
    }

    /**
     * Signer en tant qu'employé
     */
    public function signAsEmployee(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // L'employé peut signer si l'évaluation est en attente de sa signature
        return $performanceEvaluation->status === 'pending_employee';
    }

    /**
     * Créer une évaluation pour un employé
     */
    public function createEmployeeEvaluation(User $user): bool
    {
        return $user->can('create_employee_evaluations');
    }

    /**
     * Modifier une évaluation d'employé
     */
    public function editEmployeeEvaluation(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // Ne peut pas modifier si validée
        if ($performanceEvaluation->status === 'validated') {
            return false;
        }

        return $user->can('edit_employee_evaluations');
    }

    /**
     * Approuver une évaluation d'employé
     */
    public function approveEmployeeEvaluation(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        // Seulement si pending_validator
        if ($performanceEvaluation->status !== 'pending_validator') {
            return false;
        }

        return $user->can('approve_employee_evaluations');
    }

    /**
     * Restaurer une évaluation supprimée
     */
    public function restore(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        return $user->can('edit_evaluations');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, PerformanceEvaluation $performanceEvaluation): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_evaluations');
    }
}
