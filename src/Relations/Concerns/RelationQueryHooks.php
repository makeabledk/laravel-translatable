<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

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
        'chunk', 'count', 'dd', 'each', 'first', 'firstOrFail', 'get', 'getQuery', 'paginate', 'simplePaginate',
    ];

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
            $this->fireBeforeGetting();
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $this->fireBeforeGetting();

        return parent::get($columns);
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        $this->fireBeforeGetting();

        return parent::getResults();
    }

    /**
     * Fire the queued callbacks for the before-getting hook.
     */
    protected function fireBeforeGetting()
    {
        $this->performHookCallback('beforeGetting', [$this->query]);
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
