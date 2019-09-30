<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Makeable\LaravelTranslatable\Concerns\HasCurrentLanguage;
use Makeable\LaravelTranslatable\Concerns\SyncsAttributes;
use Makeable\LaravelTranslatable\Queries\BestLanguageQuery;

trait Translatable
{
    use HasCurrentLanguage,
        SyncsAttributes,
        TranslatableRelationships;

    /**
     * Register observer on model
     */
    public static function bootTranslatable()
    {
        static::observe(TranslatableObserver::class);
    }

    /**
     * @return HasMany
     */
    public function master()
    {
        return $this->belongsTo(get_class($this), $this->getMasterKeyName());
    }

    /**
     * @return HasMany
     */
    public function translations()
    {
        return $this->hasMany(get_class($this), $this->getMasterKeyName());
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
        return $query->whereNull($this->getMasterKeyName());
    }

    /**
     * @return int
     */
    public function getMasterKey()
    {
        return $this->master_id ?: $this->id;
    }

    /**
     * @return string
     */
    public function getMasterKeyName()
    {
        return 'master_id';
    }

    /**
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTranslation($language)
    {
        return $this->translations->firstWhere('language_code', $language);
    }

    /**
     * @return Builder
     */
    public function newQuery()
    {
        return tap(parent::newQuery(), function ($query) {
            if (($language = static::getCurrentLanguage()) !== null) {
                $query->language($language);
            }
        });
    }

    /**
     * @return bool
     */
    public function isMaster()
    {
        return $this->master_id === null;
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
