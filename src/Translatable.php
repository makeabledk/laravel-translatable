<?php

namespace Makeable\LaravelTranslatable;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Makeable\LaravelTranslatable\Builder\TranslatableBuilder;
use Makeable\LaravelTranslatable\Concerns\HasCurrentLanguage;
use Makeable\LaravelTranslatable\Concerns\SyncsAttributes;
use Makeable\LaravelTranslatable\Relations\VersionsRelation;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait Translatable
{
    use HasCurrentLanguage,
        SyncsAttributes,
        TranslatableRelationships;

    /**
     * Register observer on model.
     */
    public static function bootTranslatable()
    {
        static::observe(TranslatableObserver::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function master()
    {
        return $this->belongsTo(get_class($this), $this->getMasterKeyName())->withoutLanguageScope();
    }

    /**
     * Translations refer to all non-master versions. Ex.
     *
     * - Danish (master)
     * - English <-- TRANSLATION, $this instance
     * - Swedish <-- TRANSLATION,
     *
     * @return HasMany
     */
    public function translations()
    {
        return $this->hasMany(static::class, $this->getMasterKeyName())->withoutDefaultLanguageScope();
    }

    /**
     * Siblings refer to other translations than the current one (incl. master). Ex.
     *
     * - Danish (master) <-- SIBLING
     * - English <-- $this instance
     * - Swedish <-- SIBLING
     *
     * This relation should only be used for query purposes and not attaching
     * new translations as it relies on a sub-selected foreign key.
     *
     * @return VersionsRelation
     */
    public function siblings()
    {
        return VersionsRelation::model($this)->withoutSelf();
    }

    /**
     * Versions refer to all translations (incl. master). Ex.
     *
     * - Danish (master) <-- VERSION
     * - English <-- VERSIONS, $this instance
     * - Swedish <-- VERSION
     *
     * This relation should only be used for query purposes and not attaching
     * new translations as it relies on a sub-selected foreign key.
     *
     * @return VersionsRelation
     */
    public function versions()
    {
        return VersionsRelation::model($this);
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
        return LanguageScope::apply($query, $languagePriority, $fallbackMaster);
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
     * @return \Makeable\LaravelTranslatable\Translatable
     */
    public function getMaster()
    {
        return $this->isMaster() ? $this : $this->master;
    }

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
     * @param $language
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getTranslationOrNew($language)
    {
        return $this->getTranslation($language)
            ?: (new static)->forceFill(['language_code' => $language])->master()->associate($this->getMaster());
    }

    /**
     * Make sure to initialize new query with current language when set.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return tap(parent::newQuery(), Closure::fromCallable([$this, 'applyCurrentLanguage']));
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Makeable\LaravelTranslatable\Builder\TranslatableBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new TranslatableBuilder($query);
    }
}
