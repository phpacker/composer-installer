<?php

namespace PHPacker\ComposerInstaller\Concerns;

use Symfony\Component\Finder\Finder;

trait FindsConfigFile
{
    private static function findConfig(string $root = __DIR__): ?string
    {
        $finder = new Finder;
        $finder->files()
            ->in($root)
            ->exclude(['vendor', 'tests'])
            ->name('phpacker.json')
            ->depth('<= 3');

        // If the package we're installing is phpacker itself, ignore it's internal config file.
        if (substr($root, -strlen('phpacker/phpacker')) === 'phpacker/phpacker') {
            $finder->notPath('config/phpacker.json');
        }

        foreach ($finder as $file) {
            return $file->getRealPath();
        }

        return null;
    }
}
