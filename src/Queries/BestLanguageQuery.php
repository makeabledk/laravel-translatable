<?php

namespace Makeable\LaravelTranslatable\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BestLanguageQuery
{
    /**
     * @var array
     */
    protected static $appliedQueries = [];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $languages
     * @param  bool  $fallbackMaster
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke($query, $languages, $fallbackMaster = false)
    {
        $languages = $this->getPrioritizedLanguages($languages, $fallbackMaster);

        $this->clearVariables($query);

        $query->whereRaw($query->qualifyColumn('id') . " IN ({$this->getBestIdsQuery($query, $languages)})");

        static::$appliedQueries[spl_object_id($query)] = true;

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    public static function wasAppliedOn(Builder $query)
    {
        return array_key_exists(spl_object_id($query), static::$appliedQueries);
    }

    /**
     * @param $languages
     * @param $fallbackMaster
     * @return \Illuminate\Support\Collection|mixed
     */
    protected function getPrioritizedLanguages($languages, $fallbackMaster)
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
            });
    }

    /**
     * Reset previous variables that may interfere with new results
     *
     * @param  Builder  $query
     */
    protected function clearVariables(Builder $query)
    {
        $query->getConnection()->select('SELECT NULL, NULL INTO @prevPostId, @priority');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Collection  $languages
     * @return string
     */
    protected function getBestIdsQuery(Builder $query, Collection $languages)
    {
        // Create sub-queries for each language. The queries
        // fetches id's for posts of their language
        // along with a specified priority.
        $prioritizedIdsQuery = $languages
            ->map(function ($language, $priority) use ($query, $languages) {
                $query = trim("
                    SELECT id, language_code, ( 
                        SELECT IF(master_id is NULL, id, master_id)
                    ) as post_id, {$priority} as priority FROM {$query->getQuery()->from}
                ");

                // Fetch posts of specified language
                if ($language !== '*') {
                    return "{$query} WHERE language_code = '{$language}'";
                }

                // If master fallback: get master posts except the for the languages we've already fetched
                return "{$query} WHERE language_code NOT IN ('" . $languages->implode("','") . "') && master_id IS NULL";
            })
            // Now union the language queries
            ->pipe(function (Collection $queries) {
                return $queries->implode(' UNION DISTINCT ') . ' ORDER BY post_id asc, priority asc';
            });

        // Now we'll use the previous priorities and select the best match.
        // We'll return all the actual id's of the posts we want to fetch.
        return '
            SELECT id FROM (
                SELECT id, @priority := IF(@prevPostId <> post_id OR @prevPostId IS NULL, 1, @priority + 1) AS priority, @prevPostId:=post_id as post_id, language_code
                FROM (' . $prioritizedIdsQuery . ') as prioritized_query 
                HAVING priority = 1
            ) as best_ids_query
        ';
    }
}