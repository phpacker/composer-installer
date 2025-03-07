<?php

namespace PHPacker\ComposerInstaller\Concerns;

trait DetectsPlatform
{
    protected function detectPlatformAndArchitecture(): array
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
}
