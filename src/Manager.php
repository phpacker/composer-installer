<?php

namespace PHPacker\ComposerInstaller;

use PHPacker\ComposerInstaller\Concerns\FindsConfigFile;
use PHPacker\ComposerInstaller\Concerns\InteractsWithFiles;

class Manager
{
    use FindsConfigFile;
    use InteractsWithFiles;

    public static function install()
    {
        $configPath = self::findConfig();

        if (! $configPath) {
            return; // TODO: Display some warning?
        }

        $config = self::readJsonFile($configPath);

        print_r($config);
    }

    public static function uninstall()
    {
        echo PHP_EOL;
        echo 'UNINSTALL!';
        echo PHP_EOL;
        echo PHP_EOL;
    }
}
