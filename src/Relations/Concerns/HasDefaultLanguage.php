<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasDefaultLanguage
{
    use RelationQueryHooks;

    /**
     * @var bool
     */
    protected $applyDefaultLanguage = true;

    /**
     * Fetch all related models in relationship including translations.
     * Standard behavior is that it only fetches the best matching
     * version to the current language of the parent.
     *
     * @return $this
     */
    public function withoutLanguageScope()
    {
        $this->applyDefaultLanguage = false;

        return $this;
    }


    /**
     * Check what was actually the latest requested language for the model.
     * Only in case we can't retrieve that, we'll default to the
     * language of the current model.
     *
     * This is useful for eager-loaded queries where we wish to persist
     * the same language preferences throughout the entire nested queries.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return void
     */
    protected function setDefaultLanguageFromLatestQuery(Model $model = null)
    {
        if ($model &&
            ModelChecker::checkTranslatable($model) &&
            $language = LanguageScope::getLatestRequestedLanguage($model)
        ) {
            // Ensure we always default to master
            $this->setDefaultLanguage(array_merge($language, ['*']));
            return;
        }

        $this->setDefaultLanguageFromModel($model);
    }

    /**
     * Set the default language to match the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return void
     */
    protected function setDefaultLanguageFromModel(Model $model = null)
    {
        // Sometimes the parent will be an empty instance. In this case
        // we won't set any default language based on that.
        if (! optional($model)->exists) {
            return;
        }

        $this->setDefaultLanguage([$model->language_code, '*']);

//        if (ModelChecker::checkTranslatable($model)) {
//            // We'll check what was actually requested latest for the model.
//            // Only in case we can't retrieve that, we'll default to the
//            // language of the current model.
//            $language = LanguageScope::getLatestRequestedLanguage($model) ?: [$model->language_code];
//
//            // Ensure we always default to master
//            $this->setDefaultLanguage(array_merge($language, ['*']));
//
//            return;
//        }
//
//        $this->setDefaultLanguage('*');
    }

    /**
     * Apply a default language scope unless already set by user.
     *
     * @param $language
     */
    protected function setDefaultLanguage($language)
    {
        $this->beforeGetting(function (Builder $query) use ($language) {
            if ($this->applyDefaultLanguage &&
                ModelChecker::checkTranslatable($query->getModel()) &&
                LanguageScope::wasntApplied($query)
            ) {
                LanguageScope::apply($query, $language);
            }
        });
    }
}