<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Makeable\LaravelTranslatable\ModelChecker;

trait TranslatedBelongsToConstraints
{
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

        $this->beforeGetting(function ($query) {
            $query->where(function ($query) {
                $table = $this->related->getTable();

                // If parent is not a translatable table we can match directly on the foreign keys.
                // Ie. select * from posts WHERE posts.id = {$meta->post_id}
                $query->where($table.'.'.$this->ownerKey, '=', $this->child->{$this->foreignKey});

                // If parent is translatable we'll also accept that it matches on master_id in case we're querying for a translation.
                // Ie. select * from posts WHERE posts.id = {$meta->post_id} or (posts.master_id = {$meta->post_id} and posts.master_id is not null)
                // If language scope has been explicitly disabled we'll avoid corrupting the query.
                if (ModelChecker::checkTranslatable($this->related) && $this->query->languageScopeEnabled) {
                    $query->orWhere(function ($query) use ($table) {
                        $query->where($table.'.'.$this->related->getMasterKeyName(), '=', $this->child->{$this->foreignKey})
                            ->whereNotNull($table.'.'.$this->related->getMasterKeyName());
                    });
                }
            });

            // Finally we wish to default to only fetch the parent best matching the
            // current language of the child, unless otherwise specified.
            $this->setDefaultLanguageFromModelLanguage($query, $this->child);
        });
    }
}