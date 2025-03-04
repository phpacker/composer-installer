<?php

namespace PHPacker\PHPacker;

use Exception;
use Phpacker\ComposerInstaller\Concerns\FindsConfigFile;
use Symfony\Component\Finder\Finder;
use Phpacker\ComposerInstaller\Concerns\InteractsWithFiles;

class Manager
{

    use InteractsWithFiles;
    use FindsConfigFile;

    public static function install()
    {
        $configPath = self::findConfig();

        if (! $configPath) {
            return; // TODO: Display some warning?
        }

        $config = self::readJsonFile($configPath);

        print_r($config);
    }

}
