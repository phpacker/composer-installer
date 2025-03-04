<?php

namespace Phpacker\ComposerInstaller\Concerns;

use Exception;

trait InteractsWithFiles
{
    private static function readJsonFile($path): array
    {
        if (! file_exists($path)) {
            throw new Exception("File not found: {$path}");
        }

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
            throw new Exception("Invalid file type: {$path}. Expected a JSON file.");
        }

        $jsonData = file_get_contents($path);
        if ($jsonData === false) {
            throw new Exception("Failed to read file: {$path}");
        }

        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Config decode error: ' . json_last_error_msg());
        }

        return $data;
    }
}
