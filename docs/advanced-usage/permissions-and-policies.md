---
title: Permissions and policies
sidebar_position: 3
---

# Who may do what

Everyone can see and manage their own notifications; no permission is needed, so the bell works for every user. Two permissions, registered with javaabu/permissions in the "Notifications" group, are for support staff:

| Ability | Who |
| --- | --- |
| `viewAny` | everyone (they see their own) |
| `viewOthers` | `view_others_notifications` |
| `view` | the notifiable, or `view_others_notifications` |
| `update` (mark read or unread) | the notifiable |
| `delete` | the notifiable, or `delete_others_notifications` |
| `create`, `restore`, `forceDelete`, `viewTrash` | nobody |

A notification belongs to its notifiable when both the morph type and the key match, so a user and an admin with the same id never see each other's notifications.

Run `php artisan permissions:sync` to write the permissions to the database, then give them to roles as usual:

```php
$role->givePermissionTo('view_others_notifications');
```

# Replacing the policy

The policy is `Javaabu\Notifications\Policies\NotificationPolicy`, a javaabu/permissions `ResourcePolicy` for the `notifications` resource. Replace it in any of these ways; the app always wins over the package:

```php
// config/notifications.php
'policy' => App\Policies\NotificationPolicy::class,

// config/permissions.php
'policies' => [Javaabu\Notifications\Models\DatabaseNotification::class => App\Policies\NotificationPolicy::class],

// a service provider
Gate::policy(Javaabu\Notifications\Models\DatabaseNotification::class, App\Policies\NotificationPolicy::class);
```

```php
namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Notifications\Policies\NotificationPolicy as BasePolicy;

class NotificationPolicy extends BasePolicy
{
    // Users must have a permission to see the bell at all
    public function viewAny(Model $user): bool
    {
        return $user->can('view_notifications');
    }
}
```

To register your own permissions instead of the package's, turn them off:

```php
'register_permissions' => false,
```

The controller checks every action through the policy (`viewAny`, `view`, `update`, `delete`, `viewOthers`), and answers 403 when it says no.
