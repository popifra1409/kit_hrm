<?php

namespace App\Policies;

use App\Models\DocumentCategory;
use App\Models\User;

class DocumentCategoryPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_documents');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('view_documents');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_documents');
    }

    /**
     * Modifier
     */
    public function update(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('edit_documents');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('delete_documents');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('edit_documents');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_documents');
    }
}