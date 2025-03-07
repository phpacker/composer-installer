<?php

namespace PHPacker\ComposerInstaller;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;
use PHPacker\ComposerInstaller\Concerns\FindsConfigFile;
use PHPacker\ComposerInstaller\Concerns\InteractsWithFiles;

class Manager extends LibraryInstaller implements InstallerInterface
{
    use FindsConfigFile;
    use InteractsWithFiles;

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->installExecutable($package);
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->installExecutable($target);
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        $binDir = $this->binDir();

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        $executable = $binDir . '/' . $alias;
        if ($platform === 'windows') {
            $executable = $executable . '.exe';
        }

        if (! is_file($executable)) {
            $this->io->warning("[PHPacker]: Uninstalling {$alias} - executable does not exist: '{$executable}'");

            return;
        }

        unlink($executable);
        $this->io->info("[PHPacker]: Uninstalled {$alias}");
    }

    protected function installExecutable(PackageInterface $package)
    {
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        $binDir = $this->binDir();
        $configPath = self::findConfig($this->getInstallPath($package));

        // phpacker.json could not be discovered
        if (! $configPath) {
            $this->io->error('[PHPacker]: Unable to discover phpacker.json file');

            return;
        }

        $config = self::readJsonFile($configPath);
        $srcDir = dirname($configPath) . '/' . $config['dest'] ?? 'build';

        // Configured src directory does not exist
        if (! is_dir($srcDir)) {
            $this->io->error("[PHPacker]: Binary source directory does not exist: '{$srcDir}'");

            return;
        }

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        $executable = $srcDir . '/' . $platform . '/' . "{$platform}-{$arch}";
        if ($platform === 'windows') {
            $executable = $executable . '.exe';
        }

        // Executable could not be found
        if (! is_file($executable)) {
            $this->io->error("[PHPacker]: executable {$platform}-{$arch} does not exist: '{$executable}'");

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

        $this->io->info("[PHPacker]: Installed {$alias} ({$platform}-{$arch}) '{$outputPath}'");
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

    protected function binDir(): string
    {
        return rtrim($this->composer->getConfig()->get('bin-dir'), '/');
    }
}
