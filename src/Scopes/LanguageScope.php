<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Makeable\LaravelTranslatable\ModelChecker;

class LanguageScope
{
    /**
     * @var array
     */
    protected static $queryHistory, $modelHistory = [];

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var \Illuminate\Database\Eloquent\Model|\Makeable\LaravelTranslatable\Translatable
     */
    protected $model;

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|array  $languages
     * @param  bool|null  $fallbackMaster
     * @return Builder
     */
    public static function apply(Builder $query, $languages, $fallbackMaster = false)
    {
        return call_user_func(new static($query), $languages, $fallbackMaster);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array|null
     */
    public static function getLatestRequestedLanguage(Model $model)
    {
        return Arr::get(static::$modelHistory, get_class($model));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    public static function wasApplied(Builder $query)
    {
        return (new static($query))->getQueryHistory() !== null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    public static function wasntApplied(Builder $query)
    {
        return ! static::wasApplied($query);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
        $this->model = ModelChecker::ensureTranslatable($query->getModel());
    }

    /**
     * @param  array  $languages
     * @param  bool  $fallbackMaster
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke($languages, $fallbackMaster = false)
    {
        $languages = $this->normalizeLanguages($languages, $fallbackMaster);

//        dump($languages);

        $this->clearVariables();

        $this->pushHistory($languages);

        $this->query->whereRaw("{$this->model->getQualifiedKeyName()} IN ({$this->getBestIdsQuery($languages)})");

//        dump('matches', $this->query->count());
    }

    /**
     * @return array|null
     */
    public function getQueryHistory()
    {
        return Arr::get(static::$queryHistory, spl_object_id($this->query));
    }

    /**
     * @param  \Illuminate\Support\Collection  $languages
     * @return void
     */
    protected function pushHistory(Collection $languages)
    {
        Arr::set(static::$queryHistory, spl_object_id($this->query), $languages->toArray());
        Arr::set(static::$modelHistory, get_class($this->query->getModel()), $languages->toArray());
    }

    /**
     * @param $languages
     * @param $fallbackMaster
     * @return \Illuminate\Support\Collection|mixed
     */
    protected function normalizeLanguages($languages, $fallbackMaster)
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
     * Reset previous variables that may interfere with new results
     *
     * @return void
     */
    protected function clearVariables()
    {
        $this->query->getConnection()->select('SELECT NULL, NULL INTO @prevMasterKey, @priority');
    }

    /**
     * @param  \Illuminate\Support\Collection  $languages
     * @return string
     */
    protected function getBestIdsQuery(Collection $languages)
    {
        $primaryKeyName = $this->model->getKeyName();
        $masterKeyName = $this->model->getMasterKeyName();

        // Create sub-queries for each language. The queries
        // fetches id's for posts of their language
        // along with a specified priority.
        $prioritizedIdsQuery = $languages
            ->map(function ($language, $priority) use ($primaryKeyName, $masterKeyName, $languages) {
                $select = trim("
                    SELECT {$primaryKeyName}, language_code, ( 
                        SELECT IF({$masterKeyName} is NULL, {$primaryKeyName}, {$masterKeyName})
                    ) as master_key, {$priority} as priority FROM {$this->query->getQuery()->from}
                ");

                // Fetch posts of specified language
                if ($language !== '*') {
                    return "{$select} WHERE language_code = '{$language}'";
                }

                // If master fallback: get master posts except the for the languages we've already fetched
                return "{$select} WHERE language_code NOT IN ('{$languages->implode("','")}') && {$masterKeyName} IS NULL";
            })
            // Now union the language queries
            ->pipe(function (Collection $queries) {
                return $queries->implode(' UNION DISTINCT ') . ' ORDER BY master_key asc, priority asc';
            });

        // Now we'll use the previous priorities and select the best match.
        // We'll return all the actual id's of the posts we want to fetch.
        return "
            SELECT {$primaryKeyName} FROM (
                SELECT {$primaryKeyName}, @priority := IF(@prevMasterKey <> master_key OR @prevMasterKey IS NULL, 1, @priority + 1) AS priority, @prevMasterKey:=master_key as master_key, language_code
                FROM ({$prioritizedIdsQuery}) as prioritized_query 
                HAVING priority = 1
            ) as best_ids_query
        ";
    }
}