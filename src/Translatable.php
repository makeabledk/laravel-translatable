<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Makeable\LaravelTranslatable\Builder\TranslatableEloquentBuilder;
use Makeable\LaravelTranslatable\Concerns\HasLocaleQueryPreferences;
use Makeable\LaravelTranslatable\Concerns\SyncsAttributes;
use Makeable\LaravelTranslatable\Relations\SiblingsRelation;
use Makeable\LaravelTranslatable\Relations\VersionsRelation;
use Makeable\LaravelTranslatable\Scopes\ApplyLocaleScope;

trait Translatable
{
    use HasLocaleQueryPreferences,
        SyncsAttributes,
        TranslatableRelationships;

    /**
     * @var null|array
     */
    public $requestedLocale;

    /**
     * Register observer on model.
     */
    public static function bootTranslatable()
    {
        static::addGlobalScope(new ApplyLocaleScope);
        static::observe(TranslatableObserver::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function master()
    {
        return $this->belongsTo(get_class($this), TranslatableField::$master_id)->withoutLocaleScope();
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
        return $this->hasMany(static::class, TranslatableField::$master_id)->withoutDefaultLocaleScope();
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
     * @return SiblingsRelation
     */
    public function siblings()
    {
        return SiblingsRelation::model($this);
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
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeMaster($query)
    {
        return $query->whereNull($query->qualifyColumn(TranslatableField::$master_id));
    }

    /**
     * @param  Builder  $query
     * @param  mixed  $key
     * @return Builder
     */
    public function scopeWhereSiblingKey($query, $key)
    {
        return $query->where($query->qualifyColumn(TranslatableField::$sibling_id), $key);
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
     * @return bool
     */
    public function isMaster()
    {
        return $this->getAttribute(TranslatableField::$master_id) === null;
    }

    /**
     * @return mixed
     */
    public function getSiblingKey()
    {
        return $this->getAttribute(TranslatableField::$sibling_id);
    }

    /**
     * @param  string  $locale
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTranslation($locale)
    {
        return ($locale === $this->getAttribute(TranslatableField::$locale))
            ? $this
            : $this->siblings->firstWhere(TranslatableField::$locale, $locale);
    }

    /**
     * @param  string  $locale
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getTranslationOrNew($locale)
    {
        return $this->getTranslation($locale)
            ?: (new static)->forceFill([TranslatableField::$locale => $locale])->master()->associate($this->getMaster());
    }

    /**
     * Ensure that the denormalized sibling_id is up to date. If it
     * needs update we'll do it silently to ensure it does not
     * trigger further syncing attempts with siblings.
     *
     * @return $this
     */
    public function refreshSiblingId()
    {
        [$masterIdField, $siblingIdField] = [
            TranslatableField::$master_id,
            TranslatableField::$sibling_id,
        ];

        $freshSiblingId = $this->{$masterIdField} ?? $this->getKey();

        if ($this->{$siblingIdField} !== $freshSiblingId) {
            static::withoutEvents(function () use ($siblingIdField, $freshSiblingId) {
                $this->getConnection()->table($this->getTable())
                    ->where($this->getKeyName(), $this->getKey())
                    ->update([$siblingIdField => $freshSiblingId]);

                $this->{$siblingIdField} = $freshSiblingId;
                $this->syncOriginalAttribute($siblingIdField);
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
