<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Model;

class TranslatableObserver
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @throws \Throwable
     */
    public function creating(Model $model)
    {
        $this->ensureTranslatable($model);

        if (! $model->isMaster()) {
            $model->forceFillMissing($model->master->getSyncAttributes());
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function saved(Model $model)
    {
        $this->ensureTranslatable($model);

        if ($this->shouldSyncSiblings($model)) {
            $model
                ->translations
                ->values()
                ->push($model->master) // currently necessary because translations doesn't include master
                ->filter() // in case master is null
                ->each(function ($translation) use ($model) {
                    if (! $translation->is($model)) {
                        $translation->syncingInProgress = true;
                        $translation->forceFill($model->getSyncAttributes())->save();
                        $translation->syncingInProgress = false;
                    }
                });
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function shouldSyncSiblings(Model $model)
    {
        // Prevent infinite recursion when update origins from a sync
        if ($model->syncingInProgress) {
            return false;
        }

        $changes = $model->isMaster()
            ? $model->getChangedSyncAttributes()
            : $model->getChangedSyncAttributes($model->master->getAttributes());

        return count($changes) > 0;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \Throwable
     */
    protected function ensureTranslatable($model)
    {
        throw_unless(array_key_exists(Translatable::class, class_uses($model)), \BadMethodCallException::class);
    }
}