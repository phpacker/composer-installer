# PHPacker Composer Installer

Composer vendor binaries do not natively support automatic binary selection based on platform and architecture.

This composer plugin automates installation & removal of platform specific executable created with [PHPacker](https://github.com/phpacker/phpacker).

## Usage

Install the plugin:

```shell
composer require phpacker/composer-installer
```

Then add the following to the `extra` section of your `composer.json`:

```json
"extra": {
    "phpacker-install": "my-app",
}
```
