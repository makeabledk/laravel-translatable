<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TranslatableObserver
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function creating(Model $model)
    {
        ModelChecker::ensureTranslatable($model);

        if (! $model->isMaster()) {
            $model->forceFillMissing($model->master->getSyncAttributes());
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function created(Model $model)
    {
        // Set the master_key attribute for the first time. We'll ensure
        // to do it completely silently so we don't interfere with
        // other listeners and change-tracking features.
        Model::withoutEvents(function () use ($model) {
            DB::table($model->getTable())->where($model->getKeyName(), $model->getKey())->update([
                'master_key' => $masterKey =  $model->master_id ?? $model->getKey()
            ]);

            $model->master_key = $masterKey;
            $model->syncOriginalAttribute('master_key');
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function updating(Model $model)
    {
        $model->master_key = $model->master_id ?? $model->id;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function saved(Model $model)
    {
        ModelChecker::ensureTranslatable($model);

        if ($this->shouldSyncSiblings($model)) {
            $model
                ->siblings
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
            // In case model was just inserted, there will be no original values to check against.
            // Therefore we'll compare against the master-attributes and see if any sync-attributes were changed.
            : $model->getChangedSyncAttributes($model->master->getAttributes());

        return count($changes) > 0;
    }
}
