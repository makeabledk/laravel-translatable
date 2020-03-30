<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Makeable\LaravelTranslatable\Builder\EloquentBuilder;
use Makeable\LaravelTranslatable\ModelChecker;

class LanguageScope
{
    /**
     * @var EloquentBuilder
     */
    protected $query;

    /**
     * @var \Illuminate\Database\Eloquent\Model|\Makeable\LaravelTranslatable\Translatable
     */
    protected $model;

    /**
     * @var string
     */
    protected $primaryKeyName;

    /**
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $query
     * @param  string|array  $languages
     * @param  bool|null  $fallbackMaster
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function apply($query, $languages, $fallbackMaster = false)
    {
        return call_user_func(new static($query), $languages, $fallbackMaster);
    }

    /**
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $query
     */
    public function __construct($query)
    {
        $this->query = $query;
        $this->model = ModelChecker::ensureTranslatable($query->getModel());
        $this->primaryKeyName = $this->model->getKeyName();
    }

    /**
     * @param  array  $languages
     * @param  bool  $fallbackMaster
     * @return  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $query
     */
    public function __invoke($languages, $fallbackMaster = false)
    {
        return $this->query->whereIn(
            $this->model->getQualifiedKeyName(),
            $this->bestModelIdsQuery(static::getNormalizedLanguages($languages, $fallbackMaster))
        );
    }

    /**
     * @param  string|array  $languages
     * @param  bool|null  $fallbackMaster
     * @return \Illuminate\Support\Collection|mixed
     */
    public static function getNormalizedLanguages($languages, $fallbackMaster)
    {
        return collect($languages)
            ->values()
            ->when($fallbackMaster, function (Collection $languages) {
                // Push an * as a last-priority wildcard to indicate master fallback
                return $languages->push('*');
            })
            ->filter(function ($language) {
                // Do some simple validation so we can inline language in SQL later on
                return preg_match('/^[a-zA-Z\*]{1,5}/', $language);
            })
            ->unique();
    }

    /**
     * @param  \Illuminate\Support\Collection  $languages
     * @return string
     */
    protected function bestModelIdsQuery(Collection $languages)
    {
        // Reset previous variables that may interfere with new results.
        $this->query->getQuery()->getConnection()->select('SELECT NULL, NULL INTO @prevMasterKey, @priority');

        return function ($query) use ($languages) {
            return $query
                ->select($this->primaryKeyName)
                ->fromSub(function (Builder $query) use ($languages) {
                    return $query
                        ->select(
                            $this->primaryKeyName,
                            DB::raw('@priority := IF(@prevMasterKey <> master_key OR @prevMasterKey IS NULL, 1, @priority + 1) AS priority'),
                            DB::raw('@prevMasterKey:=master_key as master_key, language_code')
                        )
                        ->fromSub($this->prioritizedIdsQuery($languages), 'prioritized_query')
                        ->having('priority', 1);
                }, 'best_ids_query');
        };
    }

    /**
     * @param  \Illuminate\Support\Collection  $languages
     * @return \Closure
     */
    protected function prioritizedIdsQuery(Collection $languages)
    {
        $baseQuery = $this->freshQueryWithOriginalConstraints();

        return $languages
            // For each language we'll select all ids with an assigned priority according
            // to the order of the language array, etc: [0 => 'da', 1 => 'en', ...]
            ->map(function ($language, $priority) use ($baseQuery, $languages) {
                $query = (clone $baseQuery)->select([$this->primaryKeyName, 'language_code', 'master_key', DB::raw("{$priority} as priority")]);

                // Fetch posts of specified language
                if ($language !== '*') {
                    return $query->where('language_code', $language);
                }

                // If master fallback: get master posts except the for the languages we've already fetched
                return $query->whereNotIn('language_code', $languages)->whereNull('master_id');
            })
            // Now union the language queries
            ->pipe(function (Collection $queries) {
                $query = $queries->shift();

                foreach ($queries as $unionQuery) {
                    $query->union($unionQuery);
                }

                return $query->orderBy('master_key')->orderBy('priority');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function freshQueryWithOriginalConstraints()
    {
        // We'll clone the original query to include any applied where-constraints.
        // However we'll make sure to disable language-scope and apply global
        // scopes silently to avoid infinite recursion faults.
        $outerQuery = (clone $this->query)
            ->withoutLanguageScope()
            ->applyScopesSilently()
            ->getQuery();

        // Instantiate a new query from the original model but this
        // time without any global scopes. Instead we'll merge
        // any compatible wheres from the original query.
        return (new $this->model)
            ->newQuery()
            ->withoutGlobalScopes()
            ->from($outerQuery->from)
            ->mergeWheres(...$this->getCompatibleWheres($outerQuery));
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function getCompatibleWheres(Builder $query)
    {
        [$newWheres, $newBindings] = [[], []];

        foreach ($query->wheres as $key => $where) {
            $keep = null;

            // Check all known where-fields that may contain references to other table-fields
            foreach (['column', 'first', 'second'] as $property) {
                // Where-property not present
                if (is_null($qualifiedColumn = Arr::get($where, $property))) {
                    continue;
                }

                // We won't inherent complex queries as these may origin from a relational query.
                if ($qualifiedColumn instanceof Expression) {
                    $keep = false;
                    break;
                }

                $queryTable = trim(Str::before($query->from, 'as'));
                $fieldTable = Str::contains($qualifiedColumn, '.')
                    ? Str::before($qualifiedColumn, '.')
                    : null;

                // If column was qualified with another table, we'll ignore it. It probably origins from a relational query.
                if ($fieldTable !== null && $fieldTable !== $queryTable) {
                    $keep = false;
                    break;
                }

                $keep = $keep ?? true;
            }

            if ($keep === true) {
                $newWheres[] = $where;
                $newBindings = array_merge($newBindings, $this->getBindingsForWhere($where));
            }
        }

        return [$newWheres, $newBindings];
    }

    /**
     * Since there is no easy mapping between a where clause and it's original bindings
     * we'll instead re-add bindings based on the values in the where clause.
     *
     * @param array $where
     * @return array
     */
    protected function getBindingsForWhere(array $where)
    {
        if (! Str::endsWith($where['type'], 'Raw')) {
            foreach (['value', 'values'] as $valueType) {
                if (($value = Arr::get($where, $valueType))) {
                    // Re-implementation of protected \Illuminate\Database\Query\Builder@cleanBindings
                    return array_values(array_filter(Arr::wrap($value), function ($binding) {
                        return ! $binding instanceof Expression;
                    }));
                }
            }
        }

        return [];
    }
}
