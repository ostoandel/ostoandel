<?php
namespace Ostoandel\Fake;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CakeLog
{
    public static function levels()
    {
        return [
            'emergency' => LOG_EMERG,
            'alert' => LOG_ALERT,
            'critical' => LOG_CRIT,
            'error' => LOG_ERR,
            'warning' => LOG_WARNING,
            'notice' => LOG_NOTICE,
            'info' => LOG_INFO,
            'debug' => LOG_DEBUG,
        ];
    }

    /**
     * @see \CakeLog::write()
     */
    public static function write($level, $message, $scope = [])
    {
        $levels = static::levels();

        if (is_int($level)) {
            $key = array_search($level, $levels);
            if ($key !== false) {
                $level = $key;
            }
        }

        if (!isset($levels[$level])) {
            if (is_string($level) && !$scope) {
                $scope = $level;
            }
            $level = 'error';
        }

        $scope = (array)$scope;

        $channels = [];
        foreach (static::configured() as $channel) {
            $config = Config::get("logging.channels.$channel");
            $scopes = $config['scopes'] ?? [];
            if (!$scopes || array_intersect($scopes, $scope)) {
                $channels[] = $channel;
            }
        }

        Log::stack($channels)->log($level, $message);

        return true;
    }

    public static function alert($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function critical($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function debug($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function emergency($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function error($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function info($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function notice($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    public static function warning($message, $scope = [])
    {
        static::write(__FUNCTION__, $message, $scope);
    }

    /**
     * @see \CakeLog::config()
     */
    public static function config($key, $config)
    {
        $config['name'] = $key;

        $logging = Config::get('logging');
        $default =& $logging['channels'][ $logging['default'] ];
        if ($default['driver'] === 'stack') {
            if (!in_array($key, $default['channels'])) {
                $default['channels'][] = $key;
            }
            Log::forgetChannel($logging['default']);
        }

        $logging['channels'][$key] = ['driver' => 'cake'] + $config;
        Config::set('logging', $logging);
        Log::forgetChannel($key);
    }

    /**
     * @see \CakeLog::configured()
     */
    public static function configured()
    {
        $logging = Config::get('logging');
        $default = $logging['channels'][ $logging['default'] ];
        if ($default['driver'] === 'stack') {
            return $default['channels'];
        }

        return [];
    }

}
