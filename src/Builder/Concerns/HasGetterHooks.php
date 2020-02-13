<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

trait HasGetterHooks
{
    protected $beforeGettingCallbacks = [];

//    /**
//     * @param  string  $method
//     * @param  array  $parameters
//     * @return mixed
//     */
//    public function __call($method, $parameters)
//    {
//        dump('Call '.$method);
//
//        if (in_array($method, $this->passthru)) {
//            $this->invokeBeforeGettingCallbacks();
//        }
//
//        return parent::__call($method, $parameters);
//    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyScopes()
    {
        $this->invokeBeforeGettingCallbacks();

        return parent::applyScopes();
    }

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
        collect($this->beforeGettingCallbacks)
            ->sortBy('priority')
            ->each(function ($hook) {
                call_user_func($hook['callback'], $this);
            });

        $this->beforeGettingCallbacks = [];
    }
}
