<?php
namespace Ostoandel\Database\Eloquent;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * @var \Ostoandel\Database\Eloquent\Model
     */
    protected $model;

    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Database\Eloquent\Builder::get()
     */
    public function get($columns = ['*'])
    {
        $cake = $this->model->cake();

        $builder = $this->applyScopes();

        $array = $builder->toCakeQuery();

        $event = new \CakeEvent('Model.beforeFind', $cake, [ $array ]);
        $event->break = true;
        $event->breakOn = [ false, null ];
        $event->modParams = 0;
        $cake->getEventManager()->dispatch($event);
        $array = $event->result === true ? $event->data[0] : $event->result;
        if ($event->isStopped() || !is_array($array)) {
            return [];
        }

        $builder = $builder->fromCakeQuery($array);

        $alias = $cake->alias;

        $result = $builder->query->get($columns)
            ->map(function($item) use ($alias) {
                return [ $alias => (array)$item ];
            })
            ->pipe(function($collection) use ($cake) {
                $results = $collection->all();
                $event = new \CakeEvent('Model.afterFind', $cake, [$results, false]);
                $event->modParams = 0;
                $cake->getEventManager()->dispatch($event);
                return collect($event->result);
            })
            ->map(function($item) use ($alias) {
                $bare = $item[$alias];
                unset($item[$alias]);
                return $bare + $item;
            })
            ->all();

        $models = $builder->hydrate($result)->all();
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->model->newCollection($models);
    }

    public function toCakeQuery()
    {
        $query = $this->query;

        $fields = $query->columns;
        $conditions = $this->toCakeConditions($query->wheres);
        $joins = $this->toCakeJoins($query->joins ?? []);
        $order = $this->toCakeOrder($query->orders ?? []);
        $limit = $query->limit;
        $offset = $query->offset;
        $contain = $this->toCakeContain($this->eagerLoad);

        return compact('fields', 'conditions', 'joins', 'order', 'limit', 'offset', 'contain');
    }

    protected function toCakeConditions($wheres)
    {
        $alias = $this->model->cake()->alias;

        $result = [];
        $index = 0;
        foreach ($wheres as $where) {
            $type = $where['type'];
            switch ($type) {
                case 'Basic':
                    $column = $this->aliasField($where['column'], $alias);
                    $operator = $where['operator'];
                    $value = $where['value'];
                    $condition = [ "$column $operator" => $value ];
                    break;
                case 'Null':
                    $column = $this->aliasField($where['column'], $alias);
                    $condition = [ "$column =" => null ];
                    break;
                case 'NotNull':
                    $column = $this->aliasField($where['column'], $alias);
                    $condition = [ "$column !=" => null ];
                    break;
                case 'Column':
                    $db = $this->model->cake()->getDataSource();
                    $first = $this->aliasField($where['first'], $alias);
                    $operator = $where['operator'];
                    $second = $this->aliasField($where['second'], $alias);
                    $condition = [ "? $operator ?" => [ $db->identifier($first), $db->identifier($second) ] ];
                    break;
                case 'In':
                case 'InRaw':
                    $column = $this->aliasField($where['column'], $alias);
                    $values = $where['values'];
                    $condition = [ $column => $values ];
                    break;
                case 'NotIn':
                case 'NotInRaw':
                    $column = $this->aliasField($where['column'], $alias);
                    $values = $where['values'];
                    $condition = [ "$column !=" => $values ];
                    break;
                case 'Nested':
                    $condition = $this->toCakeConditions($where['query']->wheres);
                    break;
                case 'between':
                    $column = $this->aliasField($where['column'], $alias);
                    $values = $where['values'];
                    $operator = ($where['not'] ? 'NOT ' : '') . 'BETWEEN ? AND ?';
                    $condition = [ "$column $operator" => $values ];
                    break;
                case 'raw':
                    $condition = $where['sql'];
                    break;
                default:
                    throw new \NotImplementedException([$type]);
            }

            $boolean = strtoupper($where['boolean']);
            if ($boolean === 'OR' && $result) {
                ++$index;
            }
            $result[$index][] = $condition;
        }

        return count($result) >= 2 ? ['OR' => $result] : array_shift($result);
    }

    protected function aliasField($column, $alias)
    {
        return strpos($column, '.') === false ? "$alias.$column" : $column;
    }

    /**
     *
     * @param \Illuminate\Database\Query\JoinClause[] $joins
     */
    protected function toCakeJoins($joins)
    {
        $result = [];
        foreach ($joins as $join) {
            $result[] = [
                'table' => $join->table,
                'type' => $join->type,
                'conditions' => $this->toCakeConditions($join->wheres),
            ];
        }
        return $result;
    }


    protected function toCakeOrder($orders)
    {
        $result = [];
        foreach ($orders as $order) {
            $column = $order['column'];
            $direction = $order['direction'];

            $result[$column] = $direction;
        }
        return $result;
    }

    protected function toCakeContain($relations)
    {
        $result = [];
        foreach ($relations as $relation => $query) {
            $result[$relation] = null;
        }
        return $result;
    }

    /**
     *
     * @param array $array
     * @return \Ostoandel\Database\Eloquent\Builder
     */
    public function fromCakeQuery($array)
    {
        $instance = $this->model->newModelQuery();
        $query = $instance->query;

        $this->applyCakeConditions($query, $array['conditions'] ?? []);
        $this->applyCakeJoins($query, $array['joins'] ?? []);
        $this->applyCakeOrder($query, $array['order'] ?? []);
        $query->limit = $array['limit'] ?? null;
        $query->offset = $array['offset'] ?? null;

        foreach ($array['contain'] ?? [] as $model => $null) {
            $instance->with($model);
        }

        return $instance;
    }

    /**
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array|string $conditions
     */
    protected function applyCakeConditions($query, $conditions, $boolean = 'and')
    {
        $cake = $this->model->cake();
        $alias = $cake->alias;

        $conditions = (array)$conditions;

        foreach ($conditions as $key => $condition) {
            if (!is_int($key)) {
                $condition = [ $key => $condition ];
            } else if (is_string($condition)) {
                $query->whereRaw($condition, [], $boolean);
                continue;
            }

            foreach ($condition as $key => $value) {
                $mayBeBoolean = strtolower($key);
                switch ($mayBeBoolean) {
                    case 'not':
                        $this->applyCakeConditionGroup($query, $value, 'and', "$boolean not");
                        continue 2;
                    case 'and':
                    case 'or':
                        $this->applyCakeConditionGroup($query, $value, $mayBeBoolean);
                        continue 2;
                }

                $key = preg_replace("/\b{$alias}\\.\b/", '', $key);

                list($column, $operator) = explode(' ', $key, 2) + [ 1 => '=' ];
                $operator = strtoupper($operator);
                switch ($operator) {
                    case 'BETWEEN ? AND ?':
                        $query->whereBetween($column, $value, $boolean);
                        break;
                    case 'NOT BETWEEN ? AND ?':
                        $query->whereNotBetween($column, $value, $boolean);
                        break;
                    default:
                        if (is_array($value)) {
                            $tokens = explode(' ', $key);
                            if (count($tokens) === 3 && $tokens[0] === '?' && $tokens[2] === '?') {
                                $operator = $tokens[1];
                                list($first, $second) = $value;
                                $query->whereColumn($first->value, $operator, $second->value, $boolean);
                            } else {
                                $query->whereIn($column, $value, $boolean, ($operator === '!='));
                            }
                        } else {
                            $query->where($column, $operator, $value, $boolean);
                        }
                        break;
                }
            }
        }
    }

    /**
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $conditions
     * @param string $boolean
     * @param string $groupBoolean
     */
    protected function applyCakeConditionGroup($query, $conditions, $boolean, $groupBoolean = 'and')
    {
        $query->whereNested(function($query) use ($conditions, $boolean) {
            foreach ($conditions as $key => $condition) {
                if (!is_int($key)) {
                    $condition = [ $key => $condition ];
                }
                $this->applyCakeConditions($query, $condition, $boolean);
            }
        }, $groupBoolean);
    }

    /**
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $joins
     */
    protected function applyCakeJoins($query, $joins)
    {
        foreach ($joins as $join) {
            $table = $join['table'];
            $alias = $join['alias'] ?? null;
            if ($alias) {
                $table .= ' AS ' .$alias;
            }
            $type = $join['type'] ?? 'left';
            $conditions = $join['conditions'] ?? [];

            $query->join($table, function($clause) use ($conditions) {
                /** @var \Illuminate\Database\Query\JoinClause $clause */

                $this->applyCakeConditions($clause, $conditions);
            }, null, null, $type);
        }
    }

    /**
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $order
     */
    protected function applyCakeOrder($query, $order)
    {
        foreach ($order as $column => $direction) {
            $query->orderBy($column, $direction);
        }
    }
}
