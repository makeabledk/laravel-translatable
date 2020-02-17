<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Makeable\LaravelTranslatable\Builder\TranslatableEloquentBuilder;
use Makeable\LaravelTranslatable\Concerns\HasCurrentLanguage;
use Makeable\LaravelTranslatable\Concerns\SyncsAttributes;
use Makeable\LaravelTranslatable\Relations\VersionsRelation;
use Makeable\LaravelTranslatable\Scopes\ApplyLanguageScope;

trait Translatable
{
    use HasCurrentLanguage,
        SyncsAttributes,
        TranslatableRelationships;

    /**
     * @var null|array
     */
    public $requestedLanguage;

    /**
     * Register observer on model.
     */
    public static function bootTranslatable()
    {
        static::addGlobalScope(new ApplyLanguageScope);
        static::observe(TranslatableObserver::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function master()
    {
        return $this->belongsTo(get_class($this), 'master_id')->withoutLanguageScope();
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
        return $this->hasMany(static::class, 'master_id')->withoutDefaultLanguageScope();
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
     * new translations as it relies on a denormalized foreign key.
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
     * @return Builder
     */
    public function scopeMaster($query)
    {
        return $query->whereNull('master_id');
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
        return $this->master_key;
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
        return $this->getAttribute('master_id') === null;
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
     * Ensure that the denormalized master_key is up to date. If it
     * needs update we'll do it silently to ensure it does not
     * trigger further syncing attempts with siblings.
     *
     * @return $this
     */
    public function refreshMasterKey()
    {
        if ($this->master_key !== ($masterKey = $this->master_id ?? $this->getKey())) {
            static::withoutEvents(function () use ($masterKey) {
                $this->getConnection()->table($this->getTable())
                    ->where($this->getKeyName(), $this->getKey())
                    ->update(['master_key' => $masterKey]);

                $this->master_key = $masterKey;
                $this->syncOriginalAttribute('master_key');
            });
        }

        return $this;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Makeable\LaravelTranslatable\Builder\TranslatableEloquentBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new TranslatableEloquentBuilder($query);
    }
}
