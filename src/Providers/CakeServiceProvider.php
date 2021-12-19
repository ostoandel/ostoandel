<?php
namespace Ostoandel\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Monolog\Logger;
use Ostoandel\Event\LaravelEventManager;
use Ostoandel\Log\CakeLogHandler;
use Ostoandel\Routing\ReverseRoute;
use Ostoandel\View\Factory;
use Ostoandel\Cache\CakeStore;
use Symfony\Component\HttpFoundation\Cookie;

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

        $this->app->singleton('view', function ($app) {
            $factory = new Factory($app['view.engine.resolver'], $app['view.finder'], $app['events']);
            $factory->setContainer($app);
            $factory->share('app', $app);

            return $factory;
        });

        $this->app->singleton('session', function ($app) {
            return new \Ostoandel\Session\SessionManager($app);
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

        Response::macro('fromCake', function(\CakeResponse $response, $content = null) {
            if ($content === null) {
                $content = $response->body();
            }

            if ($content instanceof \Symfony\Component\HttpFoundation\Response) {
                $symfonyResponse = $content;
            } else {
                if (is_bool($content)) {
                    $content = (int)$content;
                }
                $symfonyResponse = Response::make($content, $response->statusCode(), $response->header());
            }

            foreach ($response->cookie() as $cookie) {
                $symfonyResponse->headers->setCookie(new Cookie(
                    $cookie['name'],
                    $cookie['value'],
                    $cookie['expire'],
                    $cookie['path'],
                    $cookie['domain'],
                    $cookie['secure']
                ));
            }
            return $symfonyResponse;
        });

        Event::macro('listenForCake', function($className, $eventName, $callable) {
            Event::listen($eventName, function($event) use ($className, $callable) {
                $subject = $event->subject();
                if (is_a($subject, $className)) {
                    return $callable($subject, ...array_values($event->data));
                }
            });
        });

        Cache::extend('cake', function($app, $config) {
            $engine = $config['engine'];
            list($plugin, $engine) = pluginSplit($engine, true);
            $engine .= 'Engine';
            \App::uses('CacheEngine', 'Cache');
            \App::uses($engine, $plugin . 'Cache/Engine');
            $instance = new $engine();
            $instance->init($config);
            if ($instance->settings['probability'] && time() % $instance->settings['probability'] === 0) {
                $instance->gc();
            }
            return $this->repository(new CakeStore($instance));
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

        Event::listen(QueryExecuted::class, function($event) {
            $name = $event->connectionName;
            if (Config::get('database.default') === $name) {
                $name = 'default';
            }
            $db = \ConnectionManager::getDataSource($name);
            $db->took = $event->time;
            call_user_func([$db, 'DboSource::logQuery'], $event->sql, $event->bindings);
        });

        LaravelEventManager::instance(new LaravelEventManager());

        $this->app->afterBootstrapping(BootProviders::class, function() {
            \App::uses('Router', 'Routing');

            /** @var \Illuminate\Routing\RouteCollection $routes */
            $routes = Route::getRoutes();

            foreach (array_reverse($routes->getRoutes()) as $route) {
                /** @var \Illuminate\Routing\Route $route */
                if ($route->isFallback) {
                    continue;
                }

                \Router::connect($route->uri, $route->defaults, [
                    'routeClass' => ReverseRoute::class,
                ]);
            }

            require CONFIG . 'routes.php';
        });
    }

}
