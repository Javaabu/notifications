<?php

namespace Javaabu\Notifications\Tests;

use Javaabu\Forms\FormsServiceProvider;
use Javaabu\Manifest\ManifestServiceProvider;
use Javaabu\Notifications\NotificationsServiceProvider;
use Javaabu\Notifications\Tests\TestSupport\Models\Admin;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Permissions\Models\Permission;
use Javaabu\Permissions\Models\Role;
use Javaabu\Permissions\PermissionsServiceProvider;
use Javaabu\SmsNotifications\SmsNotificationsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permissions.guards', ['web', 'web_admin']);
        $app['config']->set('broadcasting.default', 'null');
        $app['config']->set('sms.default', 'dhiraagu');

        $app['config']->set('auth.guards', [
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'web_admin' => [
                'driver' => 'session',
                'provider' => 'admins',
            ],
        ]);

        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ],
            'admins' => [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            PermissionsServiceProvider::class,
            ManifestServiceProvider::class,
            FormsServiceProvider::class,
            SmsNotificationsServiceProvider::class,
            NotificationsServiceProvider::class,
            TestServiceProvider::class,
        ];
    }
}
