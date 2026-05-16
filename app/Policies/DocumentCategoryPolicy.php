<?php

namespace App\Policies;

use App\Models\DocumentCategory;
use App\Models\User;

class DocumentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_documents');
    }

    public function view(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('view_documents');
    }

    public function create(User $user): bool
    {
        return $user->can('create_documents');
    }

    public function update(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->can('edit_documents');
    }

    public function delete(User $user, DocumentCategory $documentCategory): bool
    {
        // Ne peut pas supprimer si des documents sont liés
        if ($documentCategory->documents()->count() > 0) {
            return false;
        }

        return $user->can('delete_documents');
    }
}
