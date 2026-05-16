<?php

namespace App\Policies;

use App\Models\EvaluationCriterion;
use App\Models\User;

class EvaluationCriterionPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_evaluations');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->can('view_evaluations');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_evaluations');
    }

    /**
     * Modifier
     */
    public function update(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->can('edit_evaluations');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->can('delete_evaluations');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->can('edit_evaluations');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_evaluations');
    }
}