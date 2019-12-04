<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ProxiesGetterFunctions;

trait QueuesQueries
{
    use ProxiesGetterFunctions;

//    TODO check
//            'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'toSql', 'dump', 'dd',
//        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection',

    /**
     * When these methods are proxied through __call to the underlying query builder
     * instance, we know that we're about to execute a SQL query. This allows
     * us to hook in with some custom logic prior to the actual SQL query.
     *
     * @return array
     */
    public static $knownQueryBuilderGetters = [
        'getBindings', 'toSql', 'dump', 'dd', 'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum',
//        'chunk', // ?
//        'chunkById', 'first', 'firstOrFail', 'get', 'getModels', 'paginate', 'simplePaginate',
    ];

//    public static $knownQueryBuilderGetters = [
//        'chunk', 'count', 'dd', 'each', 'first', 'firstOrFail', 'get', 'getModels', 'getQuery', 'paginate', 'simplePaginate', 'toSql',
//    ];

    // getModels, getQuery, toSql

    protected $hooks = [
        'beforeGetting' => [],
    ];

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
//        $knownGetters = [
//            'getBindings', 'toSql', 'dump', 'dd', 'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum',
//        ];

        if (in_array($method, static::$knownQueryBuilderGetters)) {
            $this->applyQueuedQueries();

            if (static::$TEST) {
                dump("Applied queued queries");
            }
        }

        if (static::$TEST) {
            dump("__calling ".$method);
        }

        return parent::__call($method, $parameters);
    }

//    /**
//     * @param  array  $columns
//     * @return \Illuminate\Database\Eloquent\Model[]
//     */
//    public function getModels($columns = ['*'])
//    {
//        $this->fireBeforeGetting();
//
//        return parent::getModels($columns);
//    }


    public static $TEST = false;

//    public function get($column = ['*'])
//    {
//        $this->fireBeforeGetting();
//
//
//        if (static::$TEST) {
//            dd('WHAT');
//            dump($this->query->toSql());
//        }
//
////        if (spl_object_id($this->getQuery()) === 289) {
////            dd($this->toSql());
////        }
//
//        return parent::get($column);
//    }

//    /**
//     * @return $this
//     */
//    public function applyScopes()
//    {
//        $this->fireBeforeGetting();
//
//            dump($this->query->toSql());
////        if ($this->getModel()->getTable() === 'categories') {
////        }
//
//        return $this;
//
//        return parent::applyScopes();
//
//        $query = parent::applyScopes()->getQuery();
////        $this;
//
//        return (new static($query))->fireBeforeGetting();
//    }

//    /**
//     * @return string
//     */
//    public function toSql()
//    {
//        $this->fireBeforeGetting();
//
//        return parent::toSql();
//    }

    /**
     * Fire the queued callbacks for the before-getting hook.
     */
    public function applyQueuedQueries()
    {
        if (static::$TEST) {
            dump('Applying queued hooks: ', $this->hooks['beforeGetting']);
        }

        $this->performHookCallback('beforeGetting', [$this]);

        return $this;
    }
//
//    public function applyQueuedQueries()
//    {
//        return $this->fireBeforeGetting();
//    }

    /**
     * @param  callable  $callback
     * @return $this
     */
    public function beforeGetting(callable $callback)
    {
        $this->hooks['beforeGetting'][] = $callback;

        return $this;
    }

    /**
     * @param  string  $name
     * @param  array  $args
     */
    protected function performHookCallback($name, array $args)
    {
        $hooks = tap($this->hooks[$name], function () use ($name) {
            $this->hooks[$name] = [];
        });

        foreach ($hooks as $callback) {
            call_user_func_array($callback, $args);
        }
    }

}
