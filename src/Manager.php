<?php

namespace PHPacker\ComposerInstaller;

use Composer\Package\PackageInterface;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Repository\InstalledRepositoryInterface;

class Manager extends LibraryInstaller implements InstallerInterface
{
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return parent::install($repo, $package)->then(function () use ($package) {

            $packageExtra = $package->getExtra();
            $alias = $packageExtra['phpacker-install'] ?? false;
            $installPath = $this->getInstallPath($package);

            if ($alias) {
                $this->installer()->installBinaries($package, $installPath);
            }

        });
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $packageExtra = $target->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;
        $installPath = $this->getInstallPath($target);

        // phpacker-install alias found
        if ($alias) {
            $this->installer()->removeBinaries($initial);
            $this->installer()->installBinaries($target, $installPath);
        }

        return parent::update($repo, $initial, $target);
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        if ($alias) {
            $this->installer()->removeBinaries($package);
        }

        return parent::uninstall($repo, $package);
    }

    protected function installer(): BinaryInstaller
    {
        return new ExecutableInstaller(
            $this->io,
            rtrim($this->composer->getConfig()->get('bin-dir'), '/'),
            $this->composer->getConfig()->get('bin-compat'),
            $this->filesystem,
            $this->vendorDir
        );
    }
}
