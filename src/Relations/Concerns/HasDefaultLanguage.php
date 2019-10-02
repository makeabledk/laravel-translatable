<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Makeable\LaravelTranslatable\Queries\BestLanguageQuery;

trait HasDefaultLanguage
{
    use RelationQueryHooks;

    protected $applyDefaultLanguage = true;

    /**
     * Fetch all related models in relationship including translations.
     * Standard behavior is that it only fetches the best matching
     * version to the current language of the parent.
     *
     * @return $this
     */
    public function withoutDefaultingLanguage()
    {
        $this->applyDefaultLanguage = false;

        return $this;
    }

    /**
     * Apply a default language scope unless already set by user
     *
     * @param $language
     */
    protected function setDefaultLanguage($language)
    {
        $this->beforeGetting(function (Builder $query) use ($language) {
            if ($this->applyDefaultLanguage && ! BestLanguageQuery::wasAppliedOn($query)) {
                $query->language($language);
            }
        });
    }
}