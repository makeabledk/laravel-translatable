<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Relations\Concerns\TranslatedRelation;

class TranslatedBelongsToMany extends BelongsToMany
{
    use TranslatedRelation;

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

            $relatedKey = $this->getMasterKeyName($this->related, $this->relatedKey);

            $join->on($baseTable.'.'.$relatedKey, '=', $this->getQualifiedRelatedPivotKeyName());

//            if (ModelChecker::checkTranslatable($this->related)) {
//                $join->orOn($baseTable.'.master_id', '=', $this->getQualifiedRelatedPivotKeyName());
//            }
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
        $this
            ->setDefaultLanguageFromModel($this->parent)
            ->beforeGetting(function ($query) {
                $query->where($this->getQualifiedForeignPivotKeyName(), '=', $this->getParentKey());
            });

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
        $this
            ->setDefaultLanguageFromModel(Arr::first($models))
            ->beforeGetting(function ($query) use ($models) {
                $whereIn = $this->whereInMethod($this->parent, $this->parentKey);

                $query->{$whereIn}(
                    $this->getQualifiedForeignPivotKeyName(),
                    $this->getMasterKeys($models, $this->parentKey)
                );
            });
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
            if (isset($dictionary[$key = $this->getMasterKey($model, null, $model->newQuery())])) {
                $model->setRelation(
                    $relation,
                    $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /**
     * @return mixed
     */
    protected function getParentKey()
    {
        return $this->getMasterKey($this->parent, $this->parentKey, $this->parent->newQuery());
    }
}
