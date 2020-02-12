<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

trait HasGetterHooks
{
    use ProxyGetterMethods;

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

//    /**
//     * @var array
//     */
//    protected $hooks = [
//        'beforeGetting' => [],
//    ];

    protected $beforeGettingCallbacks = [];

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
            $this->invokeBeforeGettingCallbacks();
//            $this->applyQueuedQueries();
        }

        return parent::__call($method, $parameters);
    }

//
//    /**
//     * Fire the queued callbacks for the before-getting hook.
//     */
//    public function applyQueuedQueries()
//    {
//        $this->performHookCallback('beforeGetting', [$this]);
//
//        return $this;
//    }

    /**
     * @param  callable  $callback
     * @param  int  $priority
     * @return $this
     */
    public function beforeGetting(callable $callback, $priority = 10)
    {
        $this->beforeGettingCallbacks[] = compact('callback', 'priority');

        return $this;
    }

    public function invokeBeforeGettingCallbacks()
    {
//        $hooks = tap($this->beforeGettingCallbacks, function () {
//            $this->hooks[$name] = [];
//        });

        collect($this->beforeGettingCallbacks)
            ->sortBy('priority')
            ->each(function ($hook) {
                call_user_func($hook['callback'], $this);
            });

        $this->beforeGettingCallbacks = [];

//        foreach ($hooks as $callback) {
//            call_user_func_array($callback, $args);
//        }
    }
}
