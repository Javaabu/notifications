<?php

namespace Javaabu\Notifications\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Manifest;
use Javaabu\Notifications\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(base_path('package.json'));
        File::deleteDirectory(base_path('node_modules'));

        parent::tearDown();
    }

    /**
     * An app with these npm packages in package.json and node_modules
     *
     * @param  array<string, string>  $installed
     */
    protected function app(array $installed): void
    {
        File::put(base_path('package.json'), json_encode([
            'devDependencies' => array_map(fn (string $version) => "^{$version}", $installed),
        ]));

        foreach ($installed as $name => $version) {
            File::ensureDirectoryExists(base_path("node_modules/{$name}"));
            File::put(base_path("node_modules/{$name}/package.json"), json_encode(['name' => $name, 'version' => $version]));
        }
    }

    /**
     * What manifest:check asks the app for on this package's behalf (other packages in the
     * test app have their own needs)
     *
     * @return array<string, array<string, mixed>>
     */
    protected function check(): array
    {
        Artisan::call('manifest:check', ['--json' => true]);

        return collect(json_decode(Artisan::output(), true))
            ->filter(fn (array $row) => in_array('notifications', $row['needed_by']))
            ->keyBy('name')
            ->all();
    }

    #[Test]
    public function it_registers_its_pages_and_scripts_with_manifest(): void
    {
        $manifest = app(Manifest::class);

        $this->assertTrue($manifest->has('notifications::notifications'));

        $package = $manifest->package('notifications');

        $this->assertEquals('@javaabu/notifications', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
    }

    #[Test]
    public function its_react_dependencies_are_only_for_pages(): void
    {
        $package = app(Manifest::class)->package('notifications');

        $this->assertEquals([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertEquals([], $package->npmPeerDependencies(NpmScopes::ASSETS));

        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('@javaabu/onu', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('react', $package->npmPeerDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('@inertiajs/react', $package->npmPeerDependencies(NpmScopes::PAGES));
    }

    #[Test]
    public function a_blade_app_only_needs_vite(): void
    {
        $this->app(['vite' => '7.1.0', 'laravel-vite-plugin' => '2.0.0']);

        $rows = $this->check();

        $this->assertEqualsCanonicalizing(['vite', 'laravel-vite-plugin'], array_keys($rows));
        $this->assertEquals(['ok', 'ok'], array_values(array_column($rows, 'status')));
    }

    #[Test]
    public function an_inertia_react_app_needs_the_pages_dependencies(): void
    {
        $this->app(['vite' => '7.1.0', 'laravel-vite-plugin' => '2.0.0', '@inertiajs/react' => '2.1.0']);

        $rows = $this->check();

        foreach (['react', 'react-dom', '@javaabu/forms', '@javaabu/onu'] as $name) {
            $this->assertArrayHasKey($name, $rows, "An Inertia app wasn't asked for {$name}");
            $this->assertEquals('missing', $rows[$name]['status']);
        }

        $this->assertEquals('ok', $rows['@inertiajs/react']['status']);
    }
}
