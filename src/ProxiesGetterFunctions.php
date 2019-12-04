<?php

namespace Makeable\LaravelTranslatable;

/**
 * @property \Makeable\LaravelTranslatable\Builder\TranslatableBuilder $query
 */
trait ProxiesGetterFunctions
{
    /**
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        return $this->fireHookAndProxyToParent('chunk', ...func_get_args());
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = null, $alias = null)
    {
        return $this->fireHookAndProxyToParent('chunkById', ...func_get_args());
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param  callable  $callback
     * @param  int  $count
     * @return bool
     */
    public function each(callable $callback, $count = 1000)
    {
        return $this->fireHookAndProxyToParent('each', ...func_get_args());
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        return $this->fireHookAndProxyToParent('get', ...func_get_args());
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model[]|static[]
     */
    public function getModels($columns = ['*'])
    {
        return $this->fireHookAndProxyToParent('getModels', ...func_get_args());
    }

    /**
     * Paginate the given query.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->fireHookAndProxyToParent('paginate', ...func_get_args());
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->fireHookAndProxyToParent('simplePaginate', ...func_get_args());
    }

    /**
     * @param $name
     * @param  mixed  ...$args
     * @return mixed
     */
    protected function fireHookAndProxyToParent($name, ...$args)
    {
        $this->applyQueuedQueries();

        return parent::{$name}(...$args);
    }
}