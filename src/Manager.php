<?php

namespace PHPacker\ComposerInstaller;

use Composer\IO\IOInterface;
use PHPacker\ComposerInstaller\Concerns\FindsConfigFile;
use PHPacker\ComposerInstaller\Concerns\InteractsWithFiles;

class Manager
{
    use FindsConfigFile;
    use InteractsWithFiles;

    public static function install(string $package, string $alias, IOInterface $io)
    {
        $binDir = self::binDir();
        $installPath = $binDir . '/../' . $package;
        $configPath = self::findConfig($installPath);

        // phpacker.json could not be discovered
        if (! $configPath) {
            $io->error('[PHPacker]: Unable to discover phpacker.json file');

            return;
        }

        $config = self::readJsonFile($configPath);
        $srcDir = dirname($configPath) . '/' . $config['dest'] ?? 'build';

        // Configured src directory does not exist
        if (! is_dir($srcDir)) {
            $io->error("[PHPacker]: Binary source directory does not exist: '{$srcDir}'");

            return;
        }

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        $executable = $srcDir . '/' . $platform . '/' . "{$platform}-{$arch}";
        if ($platform === 'windows') {
            $executable = $executable . '.exe';
        }

        // Executable could not be found
        if (! is_file($executable)) {
            $io->error("[PHPacker]: executable {$platform}-{$arch} does not exist: '{$executable}'");

            return;
        }

        $outputPath = $binDir . '/' . $alias;
        if ($platform === 'windows') {
            $outputPath = $outputPath . '.exe';
        }

        // Copy file over to bin path
        // TODO: on windows we could possibly proxy so the .exe can be omitted (same as composer handles .bat files)
        copy($executable, $outputPath);
        chmod($outputPath, 0755); // chmod +x

        $io->info("[PHPacker]: Installed {$alias} ({$platform}-{$arch}) '{$outputPath}'");
    }

    public static function uninstall(string $alias, IOInterface $io)
    {
        $binDir = self::binDir();

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        $executable = $binDir . '/' . $alias;
        if ($platform === 'windows') {
            $executable = $executable . '.exe';
        }

        if (! is_file($executable)) {
            $io->warning("[PHPacker]: Uninstalling {$alias} - executable does not exist: '{$executable}'");

            return;
        }

        unlink($executable);
        $io->info("[PHPacker]: Uninstalled {$alias}");
    }

    protected static function detectPlatformAndArchitecture(): array
    {
        // Detect platform
        $platform = 'unknown';
        $os = strtolower(PHP_OS);

        if (strpos($os, 'darwin') !== false) {
            $platform = 'mac';
        } elseif (strpos($os, 'win') !== false) {
            $platform = 'windows';
        } elseif (strpos($os, 'linux') !== false) {
            $platform = 'linux';
        }

        // Detect architecture
        $architecture = 'unknown';

        // Use php_uname to get more detailed system information
        $uname = strtolower(php_uname('m'));

        if (strpos($uname, 'arm') !== false || strpos($uname, 'aarch') !== false) {
            $architecture = 'arm';
        } elseif (strpos($uname, 'x86_64') !== false || strpos($uname, 'amd64') !== false) {
            $architecture = 'x64';
        } elseif (strpos($uname, 'x86') !== false || strpos($uname, 'i386') !== false || strpos($uname, 'i686') !== false) {
            $architecture = 'x86'; // This is 32-bit x86
        }

        return [
            $platform,
            $architecture,
        ];
    }

    protected static function binDir(): string
    {
        // As per composer docs. Only supported after v2.2
        return $_composer_bin_dir ?? getcwd() . '/vendor/bin';
    }
}
