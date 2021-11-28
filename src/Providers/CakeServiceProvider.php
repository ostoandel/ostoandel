<?php
namespace Ostoandel\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Logger;
use Ostoandel\Log\CakeLogHandler;

class CakeServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function register()
    {
        if (isset($_SERVER['HTTPS']) && !isset($_ENV['HTTPS'])) {
            $_ENV['HTTPS'] = $_SERVER['HTTPS'] !== 'off';
        }

        $this->app->singleton(\Illuminate\Routing\Contracts\ControllerDispatcher::class, function($app) {
            return new \Ostoandel\Routing\ControllerDispatcher($app);
        });

        $this->app->singleton('command.cake', function ($app) {
            return new \Ostoandel\Console\Commands\CakeCommand();
        });

        $this->commands(['command.cake']);
    }

    public function boot()
    {
        Route::macro('fallbackToCake', function () {
            $placeholder = 'fallbackPlaceholder';

            Route::any("{{$placeholder}}", function(\Illuminate\Routing\Route $route) {
                return $route->controllerDispatcher()
                ->dispatch($route, null, null);
            })
            ->where($placeholder, '.*')
            ->fallback();
        });

        Log::extend('cake', function($app, $config) {
            $engine = $config['engine'];
            list($plugin, $engine) = pluginSplit($engine, true);
            if (!Str::endsWith($engine, 'Log')) {
                $engine .= 'Log';
            }
            \App::uses($engine, $plugin . 'Log/Engine');
            $handler = new CakeLogHandler(new $engine($config), $config['types'] ?? [], $config['scopes'] ?? []);
            return new Logger($this->parseChannel($config), [ $handler ]);
        });

        defined('DS') || define('DS', DIRECTORY_SEPARATOR);
        defined('ROOT') || define('ROOT', base_path() . DS);
        defined('APP_DIR') || define('APP_DIR', basename(app_path()));
        defined('WWW_ROOT') || define('WWW_ROOT', app_path() . DS . 'webroot' . DS);
        defined('CONFIG') || define('CONFIG', app_path() . DS . 'Config' . DS);

        $boot = false; // Used in Cake/bootstrap.php
        require_once base_path('lib/Cake/bootstrap.php');
        defined('TESTS') || define('TESTS', APP . DS . 'Test' . DS);

        // Workaround for the CakeEmail class
        Config::set('email', true);
        if (file_exists(CONFIG . 'email.php')) {
            require_once CONFIG . 'email.php';
        }

        \App::uses('Router', 'Routing');
        require CONFIG . 'routes.php';
    }

}
