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
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        // phpacker-install alias found - override binaryInstaller
        if ($alias) {
            $this->binaryInstaller = $this->installer();
            parent::install($repo, $package);
        }
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $packageExtra = $target->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        // phpacker-install alias found - override binaryInstaller
        if ($alias) {
            $this->binaryInstaller = $this->installer();
            parent::update($repo, $initial, $target);
        }
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        // phpacker-install alias found - override binaryInstaller
        if ($alias) {
            $this->binaryInstaller = $this->installer();
            parent::uninstall($repo, $package);
        }
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
