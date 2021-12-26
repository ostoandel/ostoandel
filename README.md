Ostoandel is a package which enables to run CakePHP2 applications on Laravel.

![ostoandel](https://user-images.githubusercontent.com/7399393/147406374-b2a3b7df-b638-488c-9c0e-c09b6688f6f7.png)

## Requirements

- PHP >= 7.3
- Composer >= 2.0

## Installation

Add an autoload field to `composer.json`.

```json
"autoload": {
    "psr-4": {
        "App\\": "app"
    }
},
```

Require the laravel framework with composer.

```
composer require laravel/framework
```

Require this pacakge.

```
composer require "ostoandel/ostoandel:dev-master"
```

Get the skeleton of Laravel.

```
composer create-project --no-install --no-scripts laravel/laravel
```

## Setup

Loading the plugin in `app/Config/bootstrap.php`. The path parameter is required.

```php
CakePlugin::load('Ostoandel', ['path' => VENDORS . '/ostoandel/ostoandel/plugin/']);
```

Run the `Ostoandel.laravelize` command. Note that this may overwrite existing files in your application. Be sure to have a backup of your application before running this.

```php
cake Ostoandel.laravelize
```

Confirm your application is now running on Laravel.
