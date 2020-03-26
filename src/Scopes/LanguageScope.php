<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * @var int
     */
    protected static $selfJoinCount = 0;

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
        $baseQuery = $this->freshModelQuery();

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
    protected function freshModelQuery()
    {
        $class = get_class($this->model);

        $query = ($model = new $class)->newQuery()->withoutLanguageScope();

        $query->from($model->getTable().' as '.($alias = 'laravel_translatable_'.static::$selfJoinCount++));

        $query->getModel()->setTable($alias);

        $query = $query->toBase();
        $query->orders = [];

        return $query;

//        return $query->from($model->getTable().' as laravel_translable_'.static::$selfJoinCount++);
    }
}
