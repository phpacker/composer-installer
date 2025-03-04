<?php

namespace PHPacker\PHPacker;

use Exception;
use Phpacker\ComposerHooks\Concerns\FindsConfigFile;
use Symfony\Component\Finder\Finder;
use Phpacker\ComposerHooks\Concerns\InteractsWithFiles;

class Installer
{

    use InteractsWithFiles;
    use FindsConfigFile;

    public static function hook()
    {
        $configPath = self::findConfig();

        if (! $configPath) {
            return; // TODO: Display some warning?
        }

        $config = self::readJsonFile($configPath);

        print_r($config);
    }

}
