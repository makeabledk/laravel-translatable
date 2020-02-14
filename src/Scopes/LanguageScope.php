<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Support\Collection;
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
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $query
     * @param  string|array  $languages
     * @param  bool|null  $fallbackMaster
     * @return EloquentBuilder
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
    }

    /**
     * @param  array  $languages
     * @param  bool  $fallbackMaster
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke($languages, $fallbackMaster = false)
    {
        $languages = static::getNormalizedLanguages($languages, $fallbackMaster);

        $this->clearVariables();

        $this->query->whereRaw("{$this->model->getQualifiedKeyName()} IN ({$this->getBestIdsQuery($languages)})");

        return $this->query;
    }

    /**
     * @param $languages
     * @param $fallbackMaster
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
     * Reset previous variables that may interfere with new results.
     *
     * @return void
     */
    protected function clearVariables()
    {
        $this->query->getQuery()->getConnection()->select('SELECT NULL, NULL INTO @prevMasterKey, @priority');
    }

    /**
     * @param  \Illuminate\Support\Collection  $languages
     * @return string
     */
    protected function getBestIdsQuery(Collection $languages)
    {
        $primaryKeyName = $this->model->getKeyName();
        $masterKeyName = 'master_id';

        // Create sub-queries for each language. The queries
        // fetches id's for posts of their language
        // along with a specified priority.
        $prioritizedIdsQuery = $languages
            ->map(function ($language, $priority) use ($primaryKeyName, $masterKeyName, $languages) {
                $select = "SELECT {$primaryKeyName}, language_code, master_key, {$priority} as priority FROM {$this->query->getQuery()->from}";

                // Fetch posts of specified language
                if ($language !== '*') {
                    return "{$select} WHERE language_code = '{$language}'";
                }

                // If master fallback: get master posts except the for the languages we've already fetched
                return "{$select} WHERE language_code NOT IN ('{$languages->implode("','")}') && {$masterKeyName} IS NULL";
            })
            // Now union the language queries
            ->pipe(function (Collection $queries) {
                return $queries->implode(' UNION DISTINCT ').' ORDER BY master_key asc, priority asc';
            });

        // Now we'll use the previous priorities and select the best match.
        // We'll return all the actual id's of the posts we want to fetch.
        return "SELECT {$primaryKeyName} FROM (
            SELECT {$primaryKeyName}, @priority := IF(@prevMasterKey <> master_key OR @prevMasterKey IS NULL, 1, @priority + 1) AS priority, @prevMasterKey:=master_key as master_key, language_code
            FROM ({$prioritizedIdsQuery}) as prioritized_query 
            HAVING priority = 1
        ) as best_ids_query";
    }
}
