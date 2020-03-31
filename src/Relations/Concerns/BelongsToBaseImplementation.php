<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\ApplyLanguageScope;

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
            ->setDefaultLanguageFromModel($this->child)
            ->beforeGetting(function ($query) {
                // Ie. select * from posts WHERE posts.id = {$meta->post_id}
                $table = $this->related->getTable();

                $ownerKey = $this->getMasterKeyName($this->related, $this->ownerKey);

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
        if (! $this->pendingDefaultLanguage
            && ModelChecker::checkTranslatable($query->getModel())
            && ApplyLanguageScope::modeIs(ApplyLanguageScope::FETCH_ALL_LANGUAGES_BY_DEFAULT)) {
            $query->whereNull('master_id');
        }
    }
}
