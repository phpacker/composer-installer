<?php

namespace PHPacker\ComposerInstaller;

use Composer\Util\Platform;
use Composer\Util\Silencer;
use Composer\Package\PackageInterface;
use Composer\Installer\BinaryInstaller;
use PHPacker\ComposerInstaller\Concerns\DetectsPlatform;
use PHPacker\ComposerInstaller\Concerns\FindsConfigFile;
use PHPacker\ComposerInstaller\Concerns\InteractsWithFiles;

class ExecutableInstaller extends BinaryInstaller
{
    use DetectsPlatform;
    use FindsConfigFile;
    use InteractsWithFiles;

    public function installBinaries(PackageInterface $package, string $installPath, bool $warnOnOverwrite = true): void
    {

        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        // No phpacker-install alias found
        if ($alias === false) {
            return;
        }

        $executable = $this->getExecutable($installPath);

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        // Executable could not be found
        if (! is_file($executable)) {
            $this->io->error("[PHPacker]: executable {$platform}-{$arch} does not exist: '{$executable}'");

            return;
        }

        // Override default behaviour
        Platform::workaroundFilesystemIssues();

        if (! file_exists($executable)) {
            $this->io->writeError("    [PHPacker]: executable {$platform}-{$arch} does not exist: '{$executable}': file not found in package</warning>");

            return;
        }

        if (is_dir($executable)) {
            $this->io->writeError("    [PHPacker]: executable {$platform}-{$arch} does not exist: '{$executable}': found a directory at that path</warning>");

            return;
        }

        if (! $this->filesystem->isAbsolutePath($executable)) {
            // in case a custom installer returned a relative path for the
            // $package, we can now safely turn it into a absolute path (as we
            // already checked the binary's existence). The following helpers
            // will require absolute paths to work properly.
            $binPath = realpath($executable);
        }
        $this->initializeBinDir();
        $link = $this->binDir . '/' . $alias;
        if (file_exists($link)) {
            if (! is_link($link)) {
                if ($warnOnOverwrite) {
                    $this->io->writeError("    [PHPacker]: Skipped installation of bin '{$alias}' for package {$package->getName()}: name conflicts with an existing file");
                }

                return;
            }
            if (realpath($link) === realpath($executable)) {
                // It is a linked binary from a previous installation, which can be replaced with a proxy file
                $this->filesystem->unlink($link);
            }
        }

        $binCompat = $this->binCompat;
        if ($binCompat === 'auto' && (Platform::isWindows() || Platform::isWindowsSubsystemForLinux())) {
            $binCompat = 'full';
        }

        if ($binCompat === 'full') {
            $this->installFullBinaries($executable, $link, $alias, $package);
        } else {
            $this->installUnixyProxyBinaries($executable, $link);
        }
        Silencer::call('chmod', $binPath, 0777 & ~umask());

    }

    public function removeBinaries(PackageInterface $package): void
    {
        $this->initializeBinDir();

        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        // No phpacker-install alias found
        if ($alias === false) {
            return;
        }

        $link = $this->binDir . '/' . $alias;
        if (is_link($link) || file_exists($link)) { // still checking for symlinks here for legacy support
            $this->filesystem->unlink($link);
        }
        if (is_file($link . '.bat')) {
            $this->filesystem->unlink($link . '.bat');
        }

        // attempt removing the bin dir in case it is left empty
        if (is_dir($this->binDir) && $this->filesystem->isDirEmpty($this->binDir)) {
            Silencer::call('rmdir', $this->binDir);
        }
    }

    public function getExecutable(string $installPath): ?string
    {
        $configPath = self::findConfig($installPath);

        // phpacker.json could not be discovered
        if (! $configPath) {
            $this->io->error('[PHPacker]: Unable to discover phpacker.json file');

            return false;
        }

        $config = self::readJsonFile($configPath);
        $srcDir = dirname($configPath) . '/' . $config['dest'] ?? 'build';

        // Configured src directory does not exist
        if (! is_dir($srcDir)) {
            $this->io->error("[PHPacker]: Binary source directory does not exist: '{$srcDir}'");

            return false;
        }

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        $executable = $srcDir . '/' . $platform . '/' . "{$platform}-{$arch}";
        if ($platform === 'windows') {
            $executable = $executable . '.exe';
        }

        return $executable;
    }
}
