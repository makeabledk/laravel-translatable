<?php

namespace Makeable\LaravelTranslatable\Relations;

trait RelationQueryHooks
{
    /**
     * When these methods are proxied through __call to the underlying query builder
     * instance, we know that we're about to execute a SQL query. This allows
     * us to hook in with some custom logic prior to the actual SQL query.
     *
     * @return array
     */
    public static $knownQueryBuilderGetters = [
        'chunk', 'count', 'each', 'first', 'firstOrFail', 'get', 'getQuery', 'paginate', 'simplePaginate'
    ];

    protected $hooks = [
        'beforeGetting' => []
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
            $this->performHookCallback('beforeGetting', [$this->query]);
        }

        return parent::__call($method, $parameters);

//        if (static::hasMacro($method)) {
//            return $this->macroCall($method, $parameters);
//        }
//
//        $result = $this->forwardCallTo($this->query, $method, $parameters);
//
//        if ($result === $this->query) {
//            return $this;
//        }
//
//        return $result;
    }

    /**
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $this->performHookCallback('beforeGetting', [$this->query]);

        return parent::get($columns);
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
        foreach ($this->hooks[$name] as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}