<?php

namespace Javaabu\Notifications\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Permissions\Policies\ResourcePolicy;

/**
 * Who may see, mark and delete database notifications
 *
 * | Ability            | Who                                                       |
 * | ------------------ | --------------------------------------------------------- |
 * | viewAny            | everyone (they see their own)                             |
 * | viewOthers         | view_others_notifications                                 |
 * | view               | the notifiable, or view_others_notifications             |
 * | update (mark read) | the notifiable                                            |
 * | delete             | the notifiable, or delete_others_notifications           |
 *
 * Override it with `config('permissions.policies')` or `Gate::policy()` in the app.
 */
class NotificationPolicy extends ResourcePolicy
{
    protected string $resource = 'notifications';

    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function viewOthers(Model $user): bool
    {
        return $this->allows($user, $this->permission('view_others'));
    }

    public function view(Model $user, Model $notification): bool
    {
        if ($this->isOwner($user, $notification)) {
            return true;
        }

        return $this->viewOthers($user);
    }

    public function create(Model $user): bool
    {
        return false;
    }

    public function update(Model $user, Model $notification): bool
    {
        return $this->isOwner($user, $notification);
    }

    public function delete(Model $user, Model $notification): bool
    {
        if ($this->isOwner($user, $notification)) {
            return true;
        }

        return $this->allows($user, $this->permission('delete_others'));
    }

    public function restore(Model $user, Model $notification): bool
    {
        return false;
    }

    public function forceDelete(Model $user, Model $notification): bool
    {
        return false;
    }

    public function viewTrash(Model $user): bool
    {
        return false;
    }

    protected function isOwner(Model $user, Model $model): bool
    {
        return $model instanceof DatabaseNotification && $model->isOwnedBy($user);
    }
}
