<?php
namespace Ostoandel\Fake;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Container\EntryNotFoundException;

\App::uses('ConfigReaderInterface', 'Configure');
\App::uses('PhpReader', 'Configure');
\App::uses('Hash', 'Utility');

class Configure
{

    /**
     * \Configure::bootstrap()
     */
    public static function bootstrap()
    {
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
        return Config::has($key);
    }

    /**
     * @see \Configure::read()
     */
    public static function read($key = null)
    {
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
        $container = Container::getInstance();
        $container->instance("cake.configReader.$name", $reader);
    }

    /**
     * @see \Configure::load()
     */
    public static function load($key, $config = 'default', $merge = true)
    {
        $container = Container::getInstance();
        $id = "cake.configReader.$config";
        if ($config === 'default' && !$container->has($id)) {
            $container->instance($id, new \PhpReader());
        }

        /** @var \ConfigReaderInterface $reader */
        try {
            $reader = $container->get($id);
        } catch (EntryNotFoundException $e) {
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

}
