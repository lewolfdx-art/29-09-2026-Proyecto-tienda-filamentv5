<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmailRecipient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailRecipientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $this->allowed($authUser, 'ViewAny:EmailRecipient');
    }

    public function view(User $authUser, EmailRecipient $recipient): bool
    {
        return $this->allowed($authUser, 'View:EmailRecipient');
    }

    public function create(User $authUser): bool
    {
        return $this->allowed($authUser, 'Create:EmailRecipient');
    }

    public function update(User $authUser, EmailRecipient $recipient): bool
    {
        return $this->allowed($authUser, 'Update:EmailRecipient');
    }

    public function delete(User $authUser, EmailRecipient $recipient): bool
    {
        return $this->allowed($authUser, 'Delete:EmailRecipient');
    }

    public function deleteAny(User $authUser): bool
    {
        return $this->allowed($authUser, 'DeleteAny:EmailRecipient');
    }

    public function restore(User $authUser, EmailRecipient $recipient): bool
    {
        return $this->allowed($authUser, 'Restore:EmailRecipient');
    }

    public function forceDelete(User $authUser, EmailRecipient $recipient): bool
    {
        return $this->allowed($authUser, 'ForceDelete:EmailRecipient');
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $this->allowed($authUser, 'ForceDeleteAny:EmailRecipient');
    }

    public function restoreAny(User $authUser): bool
    {
        return $this->allowed($authUser, 'RestoreAny:EmailRecipient');
    }

    public function replicate(User $authUser, EmailRecipient $recipient): bool
    {
        return $this->allowed($authUser, 'Replicate:EmailRecipient');
    }

    public function reorder(User $authUser): bool
    {
        return $this->allowed($authUser, 'Reorder:EmailRecipient');
    }

    private function allowed(User $user, string $permission): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador'])
            && $user->can($permission);
    }
}
