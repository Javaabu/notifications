<?php

namespace Javaabu\Notifications\Tests;

use Javaabu\Notifications\NotificationsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NotificationsServiceProvider::class,
        ];
    }
}
