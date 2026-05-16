<?php

namespace App\Policies;

use App\Models\MedicalActivity;
use App\Models\User;

class MedicalActivityPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('create_medical_activities');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, MedicalActivity $medicalActivity): bool
    {
        return $user->can('create_medical_activities');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_medical_activities');
    }

    /**
     * Modifier
     */
    public function update(User $user, MedicalActivity $medicalActivity): bool
    {
        return $user->can('edit_medical_activities');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, MedicalActivity $medicalActivity): bool
    {
        return $user->can('delete_medical_activities');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, MedicalActivity $medicalActivity): bool
    {
        return $user->can('edit_medical_activities');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, MedicalActivity $medicalActivity): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_medical_activities');
    }
}