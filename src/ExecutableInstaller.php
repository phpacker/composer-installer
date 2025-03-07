<?php

namespace PHPacker\ComposerInstaller;

use Composer\Util\Platform;
use Composer\Util\Silencer;
use Composer\Package\PackageInterface;
use Composer\Installer\BinaryInstaller;
use PHPacker\ComposerInstaller\Concerns\DetectsPlatform;
use PHPacker\ComposerInstaller\Concerns\DetectsConfigFile;
use PHPacker\ComposerInstaller\Concerns\InteractsWithFiles;

class ExecutableInstaller extends BinaryInstaller
{
    use DetectsConfigFile;
    use DetectsPlatform;
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

        if (! $executable) {
            // Something went wrong in getExecutabe. Warning already triggered there.
            return;
        }

        [$platform, $arch] = self::detectPlatformAndArchitecture();

        // Override default behaviour
        Platform::workaroundFilesystemIssues();

        if (! file_exists($executable)) {
            $this->io->writeError("    <warning>[PHPacker]: executable {$platform}-{$arch} does not exist - file not found in package '{$executable}'</warning>");

            return;
        }

        if (is_dir($executable)) {
            $this->io->writeError("    <warning>[PHPacker]: executable {$platform}-{$arch} does not exist - found a directory at that path '{$executable}'</warning>");

            return;
        }

        if (! $this->filesystem->isAbsolutePath($executable)) {
            // in case a custom installer returned a relative path for the
            // $package, we can now safely turn it into a absolute path (as we
            // already checked the binary's existence). The following helpers
            // will require absolute paths to work properly.
            $executable = realpath($executable);
        }

        $this->initializeBinDir();
        $link = $this->binDir . '/' . $alias;

        if (file_exists($link)) {

            if (! is_link($link)) {

                if ($warnOnOverwrite) {
                    $this->io->writeError("    <warning>[PHPacker]: Skipped installation of bin '{$alias}' for package {$package->getName()} - name conflicts with an existing file");
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

        Silencer::call('chmod', $link, 0777 & ~umask());

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

        if (is_file($link . '.exe')) {
            $this->filesystem->unlink($link . '.bat');
        }

        // attempt removing the bin dir in case it is left empty
        if (is_dir($this->binDir) && $this->filesystem->isDirEmpty($this->binDir)) {
            Silencer::call('rmdir', $this->binDir);
        }
    }

    public function getExecutable(string $installPath): ?string
    {
        $configPath = $this->detectConfig($installPath);

        // phpacker.json could not be discovered
        if (! $configPath) {
            $this->io->writeError("    <warning>[PHPacker]: Unable to discover phpacker.json file in '{$installPath}'</warning>");

            return false;
        }

        $config = self::readJsonFile($configPath);
        $srcDir = dirname($configPath) . '/' . $config['dest'] ?? 'build';

        // Configured src directory does not exist
        if (! is_dir($srcDir)) {
            $this->io->writeError("    <warning>[PHPacker]: Binary source directory does not exist in '{$srcDir}'</warning>");

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
