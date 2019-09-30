<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Makeable\LaravelTranslatable\Concerns\HasCurrentLanguage;
use Makeable\LaravelTranslatable\Concerns\SyncsAttributes;
use Makeable\LaravelTranslatable\Queries\BestLanguageQuery;
use Makeable\LaravelTranslatable\Relations\HasManySiblings;
use Makeable\LaravelTranslatable\Relations\TranslatedHasMany;

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
     * Translations refer to all non-master versions. Ex
     *
     * - Danish (master)
     * - English <-- TRANSLATION, $this instance
     * - Swedish <-- TRANSLATION,
     *
     * @return HasMany
     */
    public function translations()
    {
        return $this->hasMany(static::class, $this->getMasterKeyName());
    }

    /**
     * Siblings refer to other translations than the current one (incl. master). Ex
     *
     * - Danish (master) <-- SIBLING
     * - English <-- $this instance
     * - Swedish <-- SIBLING
     *
     * This relation should only be used for query purposes and not attaching
     * new translations as it relies on a sub-selected foreign key.
     *
     * @return HasMany
     */
    public function siblings()
    {
        return new HasManySiblings($this->newRelatedInstance(static::class)->newQuery(), $this);
    }

    // _________________________________________________________________________________________________________________

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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMasterKey(Builder $query)
    {
        if ($query->getQuery()->columns === null) {
            $query->select($query->getQuery()->from.'.*');
        }

        return $query->selectRaw("(SELECT IF({$this->getMasterKeyName()} is NULL, {$this->getKeyName()}, {$this->getMasterKeyName()})) as master_key");
    }

    // _________________________________________________________________________________________________________________

    /**
     * @return int
     */
    public function getMasterKey()
    {
        return $this->getAttribute($this->getMasterKeyName()) ?: $this->getKey();
    }

    /**
     * @return int
     */
    public function getMasterKeyAttribute()
    {
        return $this->getMasterKey();
    }

    /**
     * @return string
     */
    public function getMasterKeyName()
    {
        return 'master_id';
    }

    /**
     * @return bool
     */
    public function isMaster()
    {
        return $this->getAttribute($this->getMasterKeyName()) === null;
    }

    /**
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTranslation($language)
    {
        return ($language === $this->language_code)
            ? $this
            : $this->siblings->firstWhere('language_code', $language);
    }

    /**
     * Make sure to initialize new query with current language when set
     *
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
}
