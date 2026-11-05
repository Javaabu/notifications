<?php

namespace Javaabu\Notifications\Tests\Unit;

use Javaabu\Notifications\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_merges_the_package_config(): void
    {
        $this->assertIsArray(config('notifications'));
    }
}
