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

//    /**
//     *
//     *
//     *
//     *
//     * @param  \Illuminate\Database\Eloquent\Collection  $results
//     * @return array
//     */
//    protected function buildDictionary(Collection $results)
//    {
//        return parent::buildDictionary(
//            $results->each(function (Model $model) {
//                if (ModelChecker::checkTranslatable($model)) {
//                    $model->setRequestedLanguage(LanguageScope::getRequestedLanguage($this->query));
//                }
//            })
//        );
//    }

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

        if (ModelChecker::checkTranslatable($model)) {
            // We'll check what was actually requested latest for the model.
            // Only in case we can't retrieve that, we'll default to the
            // language of the current model.
            $language = LanguageScope::getLatestRequestedLanguage($model) ?: [$model->language_code];

            // Ensure we always default to master
            $this->setDefaultLanguage(array_merge($language, ['*']));

            return;
        }

        $this->setDefaultLanguage('*');
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