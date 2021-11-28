<?php
namespace Ostoandel\Fake;

use Illuminate\Support\Facades\Session;

class CakeSession
{
    /**
     * @see \CakeSession::id()
     */
    public static function id($id = null)
    {
        return Session::getId();
    }

    /**
     * @see \CakeSession::started()
     */
    public static function started()
    {
        return Session::isStarted();
    }

    /**
     * @see \CakeSession::start()
     */
    public static function start()
    {
        return Session::start();
    }

    /**
     * @see \CakeSession::read()
     */
    public static function read($name)
    {
        return Session::get($name);
    }

    /**
     * @see \CakeSession::write()
     */
    public static function write($name, $value = null)
    {
        return Session::put($name, $value);
    }

    /**
     * @see \CakeSession::consume()
     */
    public static function consume($name)
    {
        $value = static::read($name);
        if ($value !== null) {
            static::delete($name);
        }
        return $value;
    }

    /**
     * @see \CakeSession::check()
     */
    public static function check($name)
    {
        return Session::has($name);
    }

    /**
     * @see \CakeSession::delete()
     */
    public static function delete($name)
    {
        return Session::forget($name);
    }

    /**
     * @see \CakeSession::renew()
     */
    public static function renew()
    {
        return Session::regenerate();
    }
}
