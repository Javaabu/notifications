<?php

namespace Javaabu\Notifications\Tests\Feature;

use Javaabu\Manifest\Manifest;
use Javaabu\Notifications\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    #[Test]
    public function it_registers_its_pages_and_scripts_with_manifest(): void
    {
        $manifest = app(Manifest::class);

        $this->assertTrue($manifest->has('notifications::notifications'));

        $package = $manifest->package('notifications');

        $this->assertEquals('@javaabu/notifications', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
        $this->assertArrayHasKey('@javaabu/onu', $package->npmDependencies());
        $this->assertArrayHasKey('react', $package->npmPeerDependencies());
    }
}
