<?php
namespace Ostoandel\Fake;

use Illuminate\Support\Facades\Config;

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
    }

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

}
