<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

trait HasGetterHooks
{
    protected $beforeGettingCallbacks = [];

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
