<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\JoinClause;
use Makeable\LaravelTranslatable\Translatable;

class TranslatedBelongsToMany extends BelongsToMany
{
    /**
     * @var string
     */
    protected $masterKeyName = 'master_id';

    /**
     * Set the join clause for the relation query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $query->join($this->table, function (JoinClause $join) {
            $baseTable = $this->related->getTable();

            $join->on($baseTable.'.'.$this->relatedKey, '=', $this->getQualifiedRelatedPivotKeyName());

            if ($this->relatedIsTranslatable()) {
                $join->orOn($baseTable.'.'.$this->masterKeyName, '=', $this->getQualifiedRelatedPivotKeyName());
            }
        });

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        $this->query->where($this->getQualifiedForeignPivotKeyName(), '=', $this->getParentKey());

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $whereIn = $this->whereInMethod($this->parent, $this->parentKey);

        $this->query->{$whereIn}(
            $this->getQualifiedForeignPivotKeyName(),
            collect($models)->map(function ($model) {
                return $this->getParentKey($model);
            })->values()->unique(null, true)->sort()->all()
        );
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $this->getModelKey($model)])) {
                $model->setRelation(
                    $relation,
                    $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /**
     * @param null $model
     * @return mixed
     */
    protected function getParentKey($model = null)
    {
        return $this->getModelKey($model ?? $this->parent, $this->parentKey);
    }

    /**
     * @param null $model
     * @param null $keyName
     * @return mixed
     */
    protected function getModelKey($model, $keyName = null)
    {
        if ($this->modelIsTranslatable($model)) {
            return $model->getMasterKey();
        }

        if ($keyName !== null) {
            return $model->{$keyName};
        }

        return $model->getKey();
    }

    /**
     * @return bool
     */
    protected function relatedIsTranslatable()
    {
        return $this->modelIsTranslatable($this->related);
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function modelIsTranslatable($model)
    {
        return array_key_exists(Translatable::class, class_uses($model));
    }
}
