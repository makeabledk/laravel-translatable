<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Model;

class TranslatableObserver
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function creating(Model $model)
    {
        // When creating translations we'll fill any syncable
        // attributes that is not already set.
        if (! $model->isMaster()) {
            $model->forceFillMissing($this->master($model)->getSyncAttributes());
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function created(Model $model)
    {
        $model->refreshSiblingId();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function updating(Model $model)
    {
        // In the rare event that we change id or master_id,
        // we'll ensure that master key is up-to-date.
        $model->setAttribute(
            TranslatableField::$sibling_id,
            $model->getAttribute(TranslatableField::$master_id) ?? $model->getKey()
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function saved(Model $model)
    {
        if ($this->shouldSyncSiblings($model)) {
            $model->siblings()->get()->each->syncAttributesFromSibling($model);
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
            // In case the model was just inserted, there will be no original attributes
            // to check against. Therefore we'll compare against the master-attributes
            // and see if any syncable attributes were changed.
            : $model->getChangedSyncAttributes($this->master($model)->getAttributes());

        return count($changes) > 0;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return Model
     */
    protected function master(Model $model)
    {
        if ($model->relationLoaded('master') && $model->master !== null) {
            return $model->master;
        }

        $model->setRelation('master', $model->master()->withoutGlobalScopes()->firstOrFail());

        return $model->master;
    }
}
