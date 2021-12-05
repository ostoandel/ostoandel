<?php
namespace Ostoandel\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

trait LaravelDatabase
{
    protected $datasource;

    public function __construct($config)
    {
        $this->datasource = $config['datasource'];
        unset($this->_connection);
        unset($this->config);
        $this->fullDebug = Config::get('app.debug');
    }

    public function __set($name, $value)
    {
        throw new \BadMethodCallException($name);
    }

    protected function getConfigKeyName() {
        if ($this->configKeyName === 'default') {
            return Config::get('database.default');
        }

        return $this->configKeyName;
    }

    public function __get($name)
    {
        switch ($name) {
            case '_connection':
                return $this->connection()->getPdo();
            case 'config':
                $config = Config::get('database.connections.' . $this->getConfigKeyName());

                $login = $config['username'] ?? '';
                unset($config['username']);

                return [
                    'datasource' => $this->datasource,
                    'login' => $login,
                ] + $config;
        }

        throw new \BadMethodCallException($name);
    }

    /**
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return DB::connection($this->getConfigKeyName());
    }

    public function connect()
    {
        $this->connection()->reconnect();
        return true;
    }

    /**
     * @see \DboSource::logQuery()
     */
    public function logQuery($sql, $params = [])
    {
        $this->connection()->logQuery($sql, $params, $this->took);
    }

    /**
     * @see \DboSource::begin()
     */
    public function begin()
    {
        $this->connection()->beginTransaction();
        return true;
    }

    /**
     * @see \DboSource::commit()
     */
    public function commit()
    {
        $this->connection()->commit();
        return true;
    }

    /**
     * @see \DboSource::rollback()
     */
    public function rollback()
    {
        $this->connection()->rollBack();
        return true;
    }
}
