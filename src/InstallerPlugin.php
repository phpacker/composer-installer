<?php

namespace PHPacker\ComposerInstaller;

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

class InstallerPlugin implements EventSubscriberInterface, PluginInterface
{
    protected IOInterface $io;

    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'packageInstall',
            'post-package-update' => 'packageInstall',
            'post-package-uninstall' => 'packageUninstall',
        ];
    }

    /*
     * Detects if the package that has the Installer Plugin installed
     * has a 'phpacker-install' value in composer.json extra section
     */
    public function packageInstall(PackageEvent $event)
    {

        $package = $event->getOperation()->getPackage();

        $packageName = $package->getPrettyName();
        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        if (is_string($alias)) {
            Manager::install($packageName, $alias, $this->io);
        }
    }

    public function packageUninstall(PackageEvent $event)
    {

        $package = $event->getOperation()->getPackage();

        $packageExtra = $package->getExtra();
        $alias = $packageExtra['phpacker-install'] ?? false;

        if (is_string($alias)) {
            Manager::uninstall($alias, $this->io);
        }
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io) {}

    public function uninstall(Composer $composer, IOInterface $io) {}
}
