<?php

namespace Makeable\LaravelTranslatable\Builder;

use Makeable\LaravelTranslatable\ProxiesGetterFunctions;

trait QueuesQueries
{
    use ProxiesGetterFunctions;

    /**
     * When these methods are proxied through __call to the underlying query builder
     * instance, we know that we're about to execute a SQL query. This allows
     * us to hook in with some custom logic prior to the actual SQL query.
     *
     * @return array
     */
    public static $knownQueryBuilderGetters = [
        'getBindings', 'toSql', 'dump', 'dd', 'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum',
    ];

    /**
     * @var array
     */
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
        if (in_array($method, static::$knownQueryBuilderGetters)) {
            $this->applyQueuedQueries();
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Fire the queued callbacks for the before-getting hook.
     */
    public function applyQueuedQueries()
    {
        $this->performHookCallback('beforeGetting', [$this]);

        return $this;
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
