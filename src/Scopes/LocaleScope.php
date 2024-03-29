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
use Makeable\LaravelTranslatable\TranslatableField;

class LocaleScope
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
     * @var string
     */
    protected $masterIdName;

    /**
     * @var string
     */
    protected $siblingIdName;

    /**
     * @var string
     */
    protected $localeName;

    /**
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $query
     * @param  string|array  $locales
     * @param  bool|null  $fallbackMaster
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function apply($query, $locales, $fallbackMaster = false)
    {
        return call_user_func(new static($query), $locales, $fallbackMaster);
    }

    /**
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $query
     */
    public function __construct($query)
    {
        $this->query = $query;
        $this->model = ModelChecker::ensureTranslatable($query->getModel());
        $this->primaryKeyName = $this->model->getKeyName();
        $this->localeName = TranslatableField::$locale;
        $this->masterIdName = TranslatableField::$master_id;
        $this->siblingIdName = TranslatableField::$sibling_id;
    }

    /**
     * @param  array  $locales
     * @param  bool  $fallbackMaster
     * @return \Makeable\LaravelTranslatable\Builder\EloquentBuilder $query
     */
    public function __invoke($locales, $fallbackMaster = false)
    {
        return $this->query->whereIn(
            $this->model->getQualifiedKeyName(),
            $this->bestModelIdsQuery(static::getNormalizedLocales($locales, $fallbackMaster))
        );
    }

    /**
     * @param  string|array  $locales
     * @param  bool|null  $fallbackMaster
     * @return \Illuminate\Support\Collection|mixed
     */
    public static function getNormalizedLocales($locales, $fallbackMaster)
    {
        return collect($locales)
            ->values()
            ->when($fallbackMaster, function (Collection $locales) {
                // Push an * as a last-priority wildcard to indicate master fallback
                return $locales->push('*');
            })
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection  $locales
     * @return string
     */
    protected function bestModelIdsQuery(Collection $locales)
    {
        // Reset previous variables that may interfere with new results.
        $this->query->getQuery()->getConnection()->select('SELECT NULL, NULL INTO @prevSiblingId, @priority');

        return function ($query) use ($locales) {
            return $query
                ->select($this->primaryKeyName)
                ->fromSub(function (Builder $query) use ($locales) {
                    return $query
                        ->select(
                            $this->primaryKeyName,
                            DB::raw("@priority := IF(@prevSiblingId <> {$this->siblingIdName} OR @prevSiblingId IS NULL, 1, @priority + 1) AS priority"),
                            DB::raw("@prevSiblingId:={$this->siblingIdName} as {$this->siblingIdName}, {$this->localeName}")
                        )
                        ->fromSub($this->prioritizedIdsQuery($locales), 'prioritized_query')
                        ->having('priority', 1);
                }, 'best_ids_query');
        };
    }

    /**
     * @param  \Illuminate\Support\Collection  $locales
     * @return \Closure
     */
    protected function prioritizedIdsQuery(Collection $locales)
    {
        $baseQuery = $this->freshQueryWithOriginalConstraints();

        return $locales
            // For each locale we'll select all ids with an assigned priority according
            // to the order of the locale array, etc: [0 => 'da', 1 => 'en', ...]
            ->map(function ($locale, $priority) use ($baseQuery, $locales) {
                $query = (clone $baseQuery)->select([
                    $this->primaryKeyName,
                    $this->localeName,
                    $this->siblingIdName,
                    DB::raw("{$priority} as priority"),
                ]);

                // Fetch posts of specified locale
                if ($locale !== '*') {
                    return $query->where($this->localeName, $locale);
                }

                // If master fallback: get master posts except the for the locales we've already fetched
                return $query
                    ->whereNotIn($this->localeName, $locales)
                    ->whereNull(TranslatableField::$master_id);
            })
            // Now union the locale queries
            ->pipe(function (Collection $queries) {
                $query = $queries->shift();

                foreach ($queries as $unionQuery) {
                    $query->union($unionQuery);
                }

                return $query->orderBy($this->siblingIdName)->orderBy('priority');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function freshQueryWithOriginalConstraints()
    {
        // We'll clone the original query to include any applied where-constraints.
        // However we'll make sure to disable locale-scope and apply global
        // scopes silently to avoid infinite recursion faults.
        $outerQuery = (clone $this->query)
            ->withoutLocaleScope()
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
     * @param  array  $where
     * @return array
     */
    protected function getBindingsForWhere(array $where)
    {
        if (! Str::endsWith($where['type'], 'Raw')) {
            foreach (['value', 'values'] as $valueType) {
                if ($value = Arr::get($where, $valueType)) {
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
