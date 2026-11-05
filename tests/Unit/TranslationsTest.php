<?php

namespace Javaabu\Notifications\Tests\Unit;

use Javaabu\Notifications\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TranslationsTest extends TestCase
{
    #[Test]
    public function english_and_dhivehi_have_the_same_keys_and_placeholders(): void
    {
        $en = $this->load('en');
        $dv = $this->load('dv');

        $this->assertEquals(array_keys($en), array_keys($dv));

        foreach ($dv as $key => $translation) {
            $this->assertNotSame('', trim($translation), "\"{$key}\" has no Dhivehi");
            $this->assertEquals($this->placeholders($key), $this->placeholders($translation), "The placeholders of \"{$key}\" differ");
        }
    }

    #[Test]
    public function every_string_the_package_shows_is_translated(): void
    {
        $en = $this->load('en');
        $missing = [];

        foreach ($this->files() as $file) {
            preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file), $matches);

            foreach ($matches[1] as $string) {
                $string = stripslashes($string);

                if (! array_key_exists($string, $en)) {
                    $missing[] = "{$string} (" . basename($file) . ')';
                }
            }
        }

        $this->assertEquals([], array_unique($missing));
    }

    #[Test]
    public function the_permission_descriptions_are_translated(): void
    {
        app()->setLocale('dv');

        $this->assertEquals('އެހެން މީހުންގެ ނޯޓިފިކޭޝަންތައް ބެލުން', __('View other users\' notifications'));
    }

    /**
     * @return array<string, string>
     */
    protected function load(string $locale): array
    {
        return json_decode(file_get_contents(__DIR__ . "/../../lang/{$locale}.json"), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, string>
     */
    protected function placeholders(string $text): array
    {
        preg_match_all('/:[a-zA-Z_]+/', $text, $matches);

        $placeholders = $matches[0];
        sort($placeholders);

        return $placeholders;
    }

    /**
     * The package's PHP and Blade files, except docblock examples
     *
     * @return array<int, string>
     */
    protected function files(): array
    {
        $files = [];

        foreach (['src', 'resources/views'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . "/../../{$directory}"));

            foreach ($iterator as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return array_filter($files, fn (string $file) => ! str_ends_with($file, 'BaseNotification.php')
            && ! str_ends_with($file, 'NotificationMessage.php'));
    }
}
