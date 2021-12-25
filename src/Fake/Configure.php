<?php
namespace Ostoandel\Fake;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

\App::uses('ConfigReaderInterface', 'Configure');
\App::uses('PhpReader', 'Configure');
\App::uses('Hash', 'Utility');

class Configure
{
    protected static $_readers = [];

    /**
     * @see \Configure::bootstrap()
     */
    public static function bootstrap()
    {
        $config = ((array)static::read('App')) + [
            'base' => false,
            'baseUrl' => false,
            'dir' => APP_DIR,
            'webroot' => WEBROOT_DIR,
            'www_root' => WWW_ROOT,
        ];
        static::write('App', $config);

        require CONFIG . 'core.php';

        \App::$bootstrapping = false;
        \App::build();

        require CONFIG . 'bootstrap.php';
    }

    /**
     * @see \Configure::check()
     */
    public static function check($key)
    {
        return Config::get("cake.$key") !== null;
    }

    /**
     * @see \Configure::read()
     */
    public static function read($key = null)
    {
        if ($key === null) {
            return (array)Config::get('cake');
        }
        return Config::get("cake.$key");
    }

    /**
     * @see \Configure::write()
     */
    public static function write($key, $value = null)
    {
        if (is_array($key)) {
            $config = [];
            foreach ($key as $k => $v) {
                $config["cake.$k"] = $v;
            }
        } else {
            $config = ["cake.$key" => $value];
        }
        Config::set($config);

        return true;
    }

    /**
     * @see \Configure::delete()
     */
    public static function delete($key)
    {
        $config = (array)Config::get('cake');
        Arr::forget($config, $key);
        Config::set('cake', $config);
    }

    /**
     * @see \Configure::consume()
     */
    public static function consume($name)
    {
        if ($name === null) {
            return null;
        }

        $value = static::read($name);
        if ($value !== null) {
            static::delete($name);
        }
        return $value;
    }

    /**
     * @see \Configure::clear()
     */
    public static function clear()
    {
        Config::set('cake', []);
        return true;
    }

    /**
     * @see \Configure::version()
     */
    public static function version()
    {
        $key = 'Cake.version';
        $version = static::read($key);
        if (!$version) {
            $config = require CAKE . 'Config/config.php';
            static::write($config);
        }
        return static::read($key);
    }

    /**
     * @see \Configure::config()
     */
    public static function config($name, \ConfigReaderInterface $reader)
    {
        static::$_readers[$name] = $reader;
    }

    /**
     * @see \Configure::configured()
     */
    public static function configured($name = null)
    {
        if ($name) {
            return isset(static::$_readers[$name]);
        }
        return array_keys(static::$_readers);
    }

    /**
     * @see \Configure::_getReader()
     */
    protected static function _getReader($config)
    {
        if (!isset(static::$_readers[$config])) {
            if ($config !== 'default') {
                return false;
            }
            static::config($config, new \PhpReader());
        }
        return static::$_readers[$config];
    }

    /**
     * @see \Configure::load()
     */
    public static function load($key, $config = 'default', $merge = true)
    {
        $reader = static::_getReader($config);
        if (!$reader) {
            return false;
        }
        $values = $reader->read($key);

        if ($merge) {
            $keys = array_keys($values);
            foreach ($keys as $key) {
                if (($c = static::read($key)) && is_array($values[$key]) && is_array($c)) {
                    $values[$key] = \Hash::merge($c, $values[$key]);
                }
            }
        }

        return static::write($values);
    }

    /**
     *
     * @see \Configure::drop()
     */
    public static function drop($name)
    {
        if (!isset(static::$_readers[$name])) {
            return false;
        }
        unset(static::$_readers[$name]);
        return true;
    }

    /**
     * @see \Configure::dump()
     */
    public static function dump($key, $config = 'default', $keys = [])
    {
        $reader = static::_getReader($config);
        if (!$reader) {
            throw new \ConfigureException(__d('cake_dev', 'There is no "%s" adapter.', $config));
        }
        if (!method_exists($reader, 'dump')) {
            throw new \ConfigureException(__d('cake_dev', 'The "%s" adapter, does not have a %s method.', $config, 'dump()'));
        }
        $values = static::read();
        if ($keys && is_array($keys)) {
            $values = array_intersect_key($values, array_flip($keys));
        }
        return (bool)$reader->dump($key, $values);
    }


    /**
     * @see \Configure::store()
     */
    public static function store($name, $cacheConfig = 'default', $data = null)
    {
        if ($data === null) {
            $data = static::read();
        }
        return \Cache::write($name, $data, $cacheConfig);
    }

    /**
     * @see \Configure::restore()
     */
    public static function restore($name, $cacheConfig = 'default')
    {
        $values = \Cache::read($name, $cacheConfig);
        if ($values) {
            return static::write($values);
        }
        return false;
    }

}
