<?php
namespace Ostoandel\Fake;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Config;

class Cache
{

    protected static function getStoreName($name)
    {
        if ($name === 'default') {
            $name = Config::get('cache.default');
        }
        return $name;
    }

    protected static function getDuration($config)
    {
        $duration = Config::get("cache.stores.$config.duration");
        if ($duration !== null && !is_int($duration)) {
            $now = new CarbonImmutable($duration);
            $duration = $now->diff($now->add($duration));
        }
        return $duration;
    }

    /**
     * @see \Cache::read()
     */
    public static function read($key, $config = 'default')
    {
        $config = static::getStoreName($config);
        if (Config::get("cache.stores.$config") === null) {
            return false;
        }
        return CacheFacade::store($config)->get($key) ?? false;
    }

    /**
     * @see \Cache::write()
     */
    public static function write($key, $value, $config = 'default')
    {
        $config = static::getStoreName($config);
        if (Config::get("cache.stores.$config") === null) {
            return false;
        }

        return CacheFacade::store($config)->put($key, $value, static::getDuration($config));
    }

    /**
     * @see \Cache::add()
     */
    public static function add($key, $value, $config = 'default')
    {
        $config = static::getStoreName($config);
        if (Config::get("cache.stores.$config") === null) {
            return false;
        }
        CacheFacade::add($key, $value, static::getDuration($config));
    }

    /**
     * @see \Cache::clear()
     */
    public static function clear($check = false, $config = 'default')
    {
        $config = static::getStoreName($config);
        if (Config::get("cache.stores.$config") === null) {
            return false;
        }
        CacheFacade::store($config)->clear();
    }

    /**
     * @see \Cache::config()
     */
    public static function config($name, $settings = [])
    {
        $name = static::getStoreName($name);

        if ($settings) {
            $settings += (array)Config::get("cache.stores.$name");
        }

        $engine = $settings['engine'] ?? null;
        if ($engine === null) {
            return false;
        }

        $settings['driver'] = 'cake';

        $settings += [
            'path' => CACHE,
        ];

        Config::set("cache.stores.$name", $settings);
    }

    /**
     * @see \Cache::settings()
     */
    public static function settings($name = 'default')
    {
        $name = static::getStoreName($name);
        $settings = Config::get("cache.stores.$name");
        if ($settings === null) {
            return [];
        }

        $settings += [
            'engine' => 'Laravel',
        ];

        return $settings;
    }

}
