<?php
namespace Ostoandel\Session;

class CakeStore extends \Illuminate\Session\Store
{

    protected $path;

    protected $params;

    public function __construct($config, \SessionHandlerInterface $handler)
    {
        $this->name = $config['cookie'];
        $this->path = $config['files'];

        $this->params = [
            'lifetime' => $config['lifetime'] * 60,
            'path' => $config['path'],
            'domain' => $config['domain'],
            'secure' => $config['secure'],
            'httponly' => $config['http_only'],
            'samesite' => $config['same_site'],
        ];
        $this->handler = $handler;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Session\Store::start()
     */
    public function start()
    {
        if (!$this->isStarted()) {
            session_set_save_handler($this->handler);
            session_set_cookie_params($this->params);
            session_save_path($this->path);
            session_name($this->name);
            if ($this->id) {
                session_id($this->id);
            }
            session_start();
            $this->id = session_id();
            $this->attributes =& $_SESSION;

            if (! $this->has('_token')) {
                $this->regenerateToken();
            }
        }

        return $this->isStarted();
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Session\Store::isStarted()
     */
    public function isStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function migrate($destroy = false)
    {
        session_regenerate_id($destroy);
        $this->id = session_id();
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Session\Store::save()
     */
    public function save()
    {
        $this->ageFlashData();
    }
}