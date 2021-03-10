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

namespace LaravelJsonApi\Eloquent\Fields\Relations;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany as EloquentMorphMany;
use LaravelJsonApi\Eloquent\Contracts\FillableToMany;
use LaravelJsonApi\Eloquent\Fields\Concerns\ReadOnly;
use LogicException;
use UnexpectedValueException;
use function sprintf;

class HasMany extends ToMany implements FillableToMany
{

    use ReadOnly;

    /**
     * Create a has-many relation.
     *
     * @param string $fieldName
     * @param string|null $relation
     * @return HasMany
     */
    public static function make(string $fieldName, string $relation = null): HasMany
    {
        return new self($fieldName, $relation);
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        if (is_array($value)) {
            $this->sync($model, $value);
            return;
        }

        throw new UnexpectedValueException('Expecting value to be an array of identifiers.');
    }

    /**
     * @inheritDoc
     */
    public function sync(Model $model, array $identifiers): iterable
    {
        $models = $this->findMany($identifiers);

        $this->doSync($model, $models);
        $model->setRelation($this->relationName(), $models);

        return $models;
    }

    /**
     * @inheritDoc
     */
    public function attach(Model $model, array $identifiers): iterable
    {
        $models = $this->findMany($identifiers);

        $this->getRelation($model)->saveMany($models);
        $model->unsetRelation($this->relationName());

        return $models;
    }

    /**
     * @inheritDoc
     */
    public function detach(Model $model, array $identifiers): iterable
    {
        $models = $this->findMany($identifiers);

        $this->doDetach($model, $models);
        $model->unsetRelation($this->relationName());

        return $models;
    }

    /**
     * @param Model $model
     * @param EloquentCollection $new
     */
    private function doSync(Model $model, EloquentCollection $new): void
    {
        $relation = $this->getRelation($model);
        $existing = $relation->get();

        $this->doDetach(
            $model,
            $existing->reject(fn($model) => $new->contains($model))
        );

        $relation->saveMany($new->reject(fn($model) => $existing->contains($model)));
    }

    /**
     * @param Model $model
     * @param EloquentCollection $remove
     */
    private function doDetach(Model $model, EloquentCollection $remove): void
    {
        $relation = $this->getRelation($model);

        /** @var Model $model */
        foreach ($remove as $model) {
            if ($relation instanceof EloquentMorphMany) {
                $model->setAttribute($relation->getMorphType(), null);
            }

            $model->setAttribute($relation->getForeignKeyName(), null)->save();
        }
    }

    /**
     * @param Model $model
     * @return EloquentHasMany|EloquentMorphMany
     */
    private function getRelation(Model $model)
    {
        $relation = $model->{$this->relationName()}();

        if ($relation instanceof EloquentHasMany || $relation instanceof EloquentMorphMany) {
            return $relation;
        }

        throw new LogicException(sprintf(
            'Expecting relation %s on model %s to be a has-many or morph-many relation.',
            $this->relationName(),
            get_class($model)
        ));
    }

}
