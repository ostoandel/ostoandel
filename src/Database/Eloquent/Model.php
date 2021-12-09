<?php
namespace Ostoandel\Database\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

\App::uses('ClassRegistry', 'Utility');

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

     /**
     * {@inheritDoc}
     * @see \Illuminate\Database\Eloquent\Model::getConnectionName()
     */
    public function getConnectionName()
    {
        $connection = static::cake()->useDbConfig;
        if ($connection === 'default') {
            $connection = Config::get('database.default');
        }
        return $connection;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Database\Eloquent\Model::newEloquentBuilder()
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * @return \Model
     */
    public static function cake()
    {
        $alias = Arr::last(explode('\\', get_called_class()));
        return \ClassRegistry::init($alias);
    }

    /**
     * {@inheritDoc}
     * @see \Illuminate\Database\Eloquent\Model::save()
     */
    public function save(array $options = array())
    {
        return static::cake()->save($this->attributes, [
            'atomic' => false,
        ] + $options);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Database\Eloquent\Model::delete()
     */
    public function delete()
    {
        return static::cake()->delete($this->getKey());
    }

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Database\Eloquent\Model::__call()
     */
    public function __call($method, $parameters)
    {
        $model = static::cake();

        $associations = $model->getAssociated();
        $associationType = $associations[$method] ?? null;
        if ($associationType !== null) {
            $settings = $model->{$associationType}[$method];

            $class = explode('\\', get_class($this));
            $class[ count($class) - 1 ] = $settings['className'];
            $className = implode('\\', $class);

            switch ($associationType) {
                case 'hasOne':
                    return $this->hasOne($className, $settings['foreignKey']);
                case 'hasMany':
                    return $this->hasMany($className, $settings['foreignKey']);
                case 'belongsTo':
                    return $this->belongsTo($className, $settings['foreignKey']);
                case 'hasAndBelongsToMany':
                    return $this->belongsToMany($className, $settings['joinTable'], $settings['foreignKey'], $settings['associationForeignKey']);
                default:
                    throw new \Exception("Unknown association type: $associationType");
            }
        }

        return parent::__call($method, $parameters);
    }

    public function toCakeArray()
    {
        return [ $this->cake()->alias => $this->attributesToArray() ] + $this->relationsToArray();
    }
}
