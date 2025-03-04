<?php

namespace Phpacker\ComposerHooks\Concerns;

use Symfony\Component\Finder\Finder;

trait FindsConfigFile
{
    private static function findConfig(): ?string
    {
        $finder = new Finder;
        $finder->files()
            ->in(dirname(__DIR__, 3))
            ->exclude(['vendor', 'tests'])
            ->name('phpacker.json')
            ->depth('<= 3');

        foreach ($finder as $file) {
            return $file->getRealPath();
        }

        return null;
    }
}
