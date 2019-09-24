<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait Translatable
{
    use TranslatableRelationships;

    /**
     * @return HasMany
     */
    public function master()
    {
        return $this->belongsTo(get_class($this), 'master_id');
    }

    /**
     * @return HasMany
     */
    public function translations()
    {
        return $this->hasMany(get_class($this), 'master_id');
    }

    /**
     * @param Builder $query
     * @param array $languagePriority
     * @param bool $fallbackMaster
     * @return Builder
     */
    public function scopeRequestLanguagePriority($query, $languagePriority, $fallbackMaster = false)
    {
        // Normalize languages. When asking for a 'master' fallback, push * as a wildcard
        $languagePriority = collect($languagePriority)
            ->values()
            ->when($fallbackMaster, function (Collection $languages) {
                return $languages->push('*');
            })
            ->filter(function ($language) {
                return preg_match('/^[a-zA-Z\*]{1,5}/', $language);
            });

        $languageQuery = $languagePriority
            // Create sub-queries for each language. The queries fetches id's
            // for posts in their language along with a specified priority.
            ->map(function ($language, $priority) use ($languagePriority) {
                $query = "SELECT id, language_code, (SELECT IF(master_id is NULL, id, master_id)) as post_id, {$priority} as priority FROM ".$this->getTable();

                return $language !== '*'
                    ? "{$query} WHERE language_code = '{$language}'"
                    : "{$query} WHERE language_code NOT IN ('".$languagePriority->implode("','")."') && master_id IS NULL"; // get master posts, except the for the languages we've already fetched
            })
            // Now union the language queries
            ->pipe(function (Collection $queries) {
                return $queries->implode(' UNION DISTINCT ').' ORDER BY post_id asc, priority asc';
            });

        // Now we'll use the previous priorities and select the best match.
        // We'll return all the actual id's of the posts we want to fetch.
        $prioritizedIdsQuery = '
            SELECT id FROM (
                SELECT id, @priority := IF(@prevPostId <> post_id OR @prevPostId IS NULL, 1, @priority + 1) AS priority, @prevPostId:=post_id as post_id, language_code
                FROM ('.$languageQuery.') as language_query 
                HAVING priority = 1
            ) as prioritized_query
        ';

        // Reset previous variables that may interfere with new results
        $query->getConnection()->select('SELECT NULL, NULL INTO @prevPostId, @priority');

        return $query->whereRaw($this->qualifyColumn('id')." IN ({$prioritizedIdsQuery})");
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeMaster($query)
    {
        return $query->whereNull('master_id');
    }

    /**
     * @return mixed
     */
    public function getMasterKey()
    {
        return $this->master_id ?: $this->id;
    }

//
//    public function scopeWithPostId($query)
//    {
//        return $query
//            ->selectDefault()
//            ->selectRaw('(SELECT IF(master_id is NULL, id, master_id)) as post_id');
//    }

//    /**
//     * @return mixed
//     */
//    public function getPostIdAttribute()
//    {
//        return $this->master_id ?: $this->id;
//    }
}
