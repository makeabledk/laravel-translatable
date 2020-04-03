<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\ApplyLocaleScope;
use Makeable\LaravelTranslatable\TranslatableField;

trait BelongsToBaseImplementation
{
    use TranslatedRelation;

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (! static::$constraints) {
            return;
        }

        $this
            ->setDefaultLocaleFromModel($this->child)
            ->beforeGetting(function ($query) {
                // Ie. select * from posts WHERE posts.id = {$meta->post_id}
                $table = $this->related->getTable();

                $ownerKey = $this->getModelKeyName($this->related, $this->ownerKey);

                $query->where($table.'.'.$ownerKey, '=', $this->child->{$this->foreignKey});

                $this->ensureMasterOnAmbiguousQueries($query);
            });
    }

    /**
     * Polyfill for different Laravel versions.
     *
     * @return mixed|string
     */
    public function getRelation()
    {
        return property_exists($this, 'relation')
            ? $this->relation
            : $this->relationName;
    }

    /**
     * Covers edge-case where present when following conditions are met:.
     *
     * - running in compatibility-mode
     * - parent is translatable
     * - child is non-translatable
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function ensureMasterOnAmbiguousQueries($query)
    {
        if (! $this->pendingDefaultLocale
            && ModelChecker::checkTranslatable($query->getModel())
            && ApplyLocaleScope::modeIs(ApplyLocaleScope::FETCH_ALL_LOCALES_BY_DEFAULT)) {
            $query->whereNull(TranslatableField::$master_id);
        }
    }
}
