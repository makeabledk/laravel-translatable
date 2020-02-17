<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

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
            });
    }
}
