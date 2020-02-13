<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Query\Builder;

class QueryBuilder extends Builder
{
    public static function fromNative(Builder $query)
    {
        $instance = new static($query->connection, $query->grammar, $query->processor);
        $properties = get_object_vars($query);

        foreach ($properties as $property => $value) {
            $instance->{$property} = $value;
        }

        return $instance;
    }
}