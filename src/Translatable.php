<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Makeable\LaravelTranslatable\Queries\BestLanguageQuery;

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
     * @param string|array $languagePriority
     * @param bool $fallbackMaster
     * @return Builder
     */
    public function scopeLanguage($query, $languagePriority, $fallbackMaster = false)
    {
        return call_user_func(new BestLanguageQuery, ...func_get_args());
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
