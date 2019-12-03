<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BuilderGetterHooks
{

//    TODO check
//            'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'toSql', 'dump', 'dd',
//        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection',

//    /**
//     * When these methods are proxied through __call to the underlying query builder
//     * instance, we know that we're about to execute a SQL query. This allows
//     * us to hook in with some custom logic prior to the actual SQL query.
//     *
//     * @return array
//     */
//    public static $knownQueryBuilderGetters = [
//        'chunk', 'count', 'dd', 'each', 'first', 'firstOrFail', 'get', 'getModels', 'getQuery', 'paginate', 'simplePaginate', 'toSql',
//    ];

    // getModels, getQuery, toSql

    protected $hooks = [
        'beforeGetting' => [],
    ];

//    /**
//     * Handle dynamic method calls to the relationship.
//     *
//     * @param  string  $method
//     * @param  array  $parameters
//     * @return mixed
//     */
//    public function __call($method, $parameters)
//    {
//        if (in_array($method, static::$knownQueryBuilderGetters)) {
//            $this->fireBeforeGetting();
//        }
//
//        return parent::__call($method, $parameters);
//    }

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

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyScopes()
    {
        $this->fireBeforeGetting();

        return parent::applyScopes();
    }

    /**
     * @return string
     */
    public function toSql()
    {
        $this->fireBeforeGetting();

        return parent::toSql();
    }

    /**
     * Fire the queued callbacks for the before-getting hook.
     */
    protected function fireBeforeGetting()
    {
        $this->performHookCallback('beforeGetting', [$this]);
    }

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
