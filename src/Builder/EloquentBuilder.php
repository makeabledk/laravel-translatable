<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Makeable\LaravelTranslatable\Builder\Concerns\HasGetterHooks;

class EloquentBuilder extends Builder
{
    use HasGetterHooks;

    /**
     * Dump a stack trace at given point in query.
     */
    public function getTrace()
    {
        dd($this->normalizeStackTrace(debug_backtrace()));
    }

    /**
     * @param $stack
     * @return array|string
     */
    protected function normalizeStackTrace($stack)
    {
        if (is_object($stack)) {
            return get_class($stack);
        }

        if (is_array($stack)) {
            return array_map(\Closure::fromCallable([$this, 'normalizeStackTrace']), $stack);
        }

        return $stack;
    }
}
