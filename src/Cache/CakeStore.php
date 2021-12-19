<?php
namespace Ostoandel\Cache;

use Illuminate\Cache\RetrievesMultipleKeys;
use Illuminate\Contracts\Cache\Store;

class CakeStore implements Store
{

    use RetrievesMultipleKeys;

    protected $engine;

    public function __construct(\CacheEngine $engine)
    {
        $this->engine = $engine;
    }

    protected function getPrefixedKey($key)
    {
        $key = $this->engine->key($key);
        if ($key === false) {
            return false;
        }
        return $this->getPrefix() . $key;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::getPrefix()
     */
    public function getPrefix()
    {
        return $this->engine->settings['prefix'];
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::get()
     */
    public function get($key)
    {
        $key = $this->getPrefixedKey($key);
        if ($key === false) {
            return null;
        }
        $data = $this->engine->read($key);
        if ($data === false) {
            return null;
        }
        return $data;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::put()
     */
    public function put($key, $value, $seconds)
    {
        $key = $this->getPrefixedKey($key);
        if ($key === false) {
            return false;
        }
        return $this->engine->write($key, $value, $seconds);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::forever()
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 100 * 365 * 24 * 60 * 60);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::increment()
     */
    public function increment($key, $value = 1)
    {
        $key = $this->getPrefixedKey($key);
        if ($key === false) {
            return false;
        }
        return $this->engine->increment($key, $value);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::decrement()
     */
    public function decrement($key, $value = 1)
    {
        $key = $this->getPrefixedKey($key);
        if ($key === false) {
            return false;
        }
        return $this->engine->decrement($key, $value);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::forget()
     */
    public function forget($key)
    {
        $key = $this->engine->key($key);
        if ($key === false) {
            return false;
        }
        return $this->engine->delete($key);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Cache\Store::flush()
     */
    public function flush()
    {
        return $this->engine->clear(false);
    }

}
