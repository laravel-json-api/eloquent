<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Hydrators;

use LaravelJsonApi\Eloquent\Fields\SoftDelete;
use LogicException;
use RuntimeException;

class SoftDeleteHydrator extends ModelHydrator
{

    /**
     * @inheritDoc
     */
    protected function persist(): void
    {
        /**
         * If the model is being restored, the Laravel restore method executes a
         * save on the model. So we only need to run the restore method and all
         * dirty attributes will be saved.
         */
        if ($this->willRestore()) {
            $this->restore();
            return;
        }

        /**
         * To ensure Laravel still executes its soft-delete logic (e.g. firing events)
         * we need to delete before a save when we are soft-deleting. Although this
         * may result in two database calls in this scenario, it means we can guarantee
         * that standard Laravel soft-delete logic is executed.
         *
         * When executing the soft delete, Laravel will apply a fresh timestamp to the
         * model's deleted at column. As the JSON:API client may have provided a different
         * timestamp, we back up that value first, execute the soft delete, then reapply
         * the timestamp.
         *
         * @see https://github.com/cloudcreativity/laravel-json-api/issues/371
         */
        if ($this->willSoftDelete()) {
            $column = $this->fieldForSoftDelete()->column();
            // save the original date so we can put it back later on.
            $deletedAt = $this->model->{$column};
            // delete the record so that deleting and deleted events get fired.
            $this->model->delete();
            // apply the original date back before saving, so that we keep the client's provded date.
            $this->model->{$column} = $deletedAt;
        }

        parent::persist();
    }

    /**
     * Restore the model.
     *
     * As the Eloquent restore method uses `$model->save()`, we also
     * persist other attributes at this point.
     */
    protected function restore(): void
    {
        if (true !== $this->model->restore()) {
            throw new RuntimeException('Failed to save resource.');
        }
    }

    /**
     * Will the hydration operation restore the model?
     *
     * @return bool
     */
    protected function willRestore(): bool
    {
        if (!$this->model->exists) {
            return false;
        }

        $column = $this->fieldForSoftDelete()->column();

        return null !== $this->model->getOriginal($column) && null === $this->model->{$column};
    }

    /**
     * Will the hydration operation result in the model being soft deleted?
     *
     * @return bool
     */
    protected function willSoftDelete(): bool
    {
        if (!$this->model->exists) {
            return false;
        }

        $column = $this->fieldForSoftDelete()->column();

        return null === $this->model->getOriginal($column) && null !== $this->model->{$column};
    }

    /**
     * @return SoftDelete
     */
    protected function fieldForSoftDelete(): SoftDelete
    {
        foreach ($this->schema->attributes() as $attribute) {
            if ($attribute instanceof SoftDelete) {
                return $attribute;
            }
        }

        throw new LogicException("Expecting schema {$this->schema->type()} to have a soft-delete field.");
    }
}
