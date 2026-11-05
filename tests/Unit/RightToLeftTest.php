<?php

namespace Javaabu\Notifications\Tests\Unit;

use Javaabu\Notifications\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RightToLeftTest extends TestCase
{
    /**
     * Mail is the exception: email clients don't support logical CSS, so the mail theme
     * switches physical properties itself for RTL locales
     */
    #[Test]
    public function views_and_components_use_logical_directions_only(): void
    {
        $physical = '/\b(ml|mr|pl|pr|left|right|rounded-l|rounded-r|border-l|border-r|scroll-ml|scroll-mr)-[0-9a-z\[]|\btext-(left|right)\b|\bfloat-(left|right)\b/';
        $found = [];

        foreach (['resources/views', 'resources/js'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . "/../../{$directory}"));

            foreach ($iterator as $file) {
                if (! $file->isFile() || str_contains($file->getPathname(), '/views/mail/')) {
                    continue;
                }

                if (preg_match($physical, file_get_contents($file->getPathname()), $match)) {
                    $found[] = "{$file->getFilename()}: {$match[0]}";
                }
            }
        }

        $this->assertEquals([], $found);
    }
}
