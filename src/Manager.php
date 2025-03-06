<?php

namespace PHPacker\ComposerInstaller;

use Composer\IO\IOInterface;
use PHPacker\ComposerInstaller\Concerns\FindsConfigFile;
use PHPacker\ComposerInstaller\Concerns\InteractsWithFiles;

class Manager
{
    use FindsConfigFile;
    use InteractsWithFiles;

    public static function install(string $package, IOInterface $io)
    {
        $configPath = self::findConfig();

        if (! $configPath) {
            return; // TODO: Display some warning?
        }

        $config = self::readJsonFile($configPath);

        print_r($config);
    }

    public static function uninstall(string $package, IOInterface $io)
    {
        echo PHP_EOL;
        echo 'UNINSTALL!';
        echo PHP_EOL;
        echo PHP_EOL;
    }
}
