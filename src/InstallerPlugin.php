<?php

namespace PHPacker\ComposerInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Plugin\PluginInterface;
use PHPacker\ComposerInstaller\Manager;
use Composer\EventDispatcher\EventSubscriberInterface;

class InstallerPlugin implements PluginInterface, EventSubscriberInterface {

    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'packageInstall',
            'post-package-update' => 'packageInstall',
            'post-package-uninstall' => 'packageUninstall',
        ];
    }

    public function packageInstall(PackageEvent $event) {

        $package = $event->getOperation()->getPackage();

        // $packageName = $package->getPrettyName();
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        if(is_string($alias)) {
            Manager::install($alias);
        }
    }

    public function packageUninstall(PackageEvent $event) {
        $package = $event->getOperation()->getPackage();

        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        if(is_string($alias)) {
            Manager::uninstall($alias);
        }
    }


    public function activate(Composer $composer, IOInterface $io) {}

    public function deactivate(Composer $composer, IOInterface $io) {}

    public function uninstall(Composer $composer, IOInterface $io) {}
}
