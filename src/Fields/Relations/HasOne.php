<?php
/*
 * Copyright 2020 Cloud Creativity Limited
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne as EloquentMorphOne;
use LogicException;

class HasOne extends BelongsTo
{

    /**
     * @inheritDoc
     */
    public function mustExist(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        $relation = $model->{$this->relation()}();

        if (!$relation instanceof EloquentHasOne && !$relation instanceof EloquentMorphOne) {
            throw new LogicException('Expecting an Eloquent has-one or morph-one relation.');
        }

        /** @var Model|null $current */
        $current = $model->{$this->relation()};
        $related = $this->find($value);

        if ($this->willChange($current, $related)) {
            $current ? $this->clear($relation, $current) : null;
            $related ? $relation->save($related) : null;
            $model->setRelation($this->relation(), $related);
        }
    }

    /**
     * Replace the relationship.
     *
     * @param Model $model
     * @param $value
     * @return Model|null
     */
    public function replace(Model $model, $value): ?Model
    {
        $this->fill($model, $value);

        return $model->getRelation($this->relation());
    }

    /**
     * @param Model|null $current
     * @param Model|null $new
     * @return bool
     */
    private function willChange(?Model $current, ?Model $new): bool
    {
        if ($current) {
            return $current->isNot($new);
        }

        return !!$new;
    }

    /**
     * @param EloquentMorphOne|EloquentHasOne $relation
     * @param Model $current
     */
    private function clear($relation, Model $current): void
    {
        if ($relation instanceof EloquentMorphOne) {
            $current->setAttribute($relation->getMorphType(), null);
        }

        $current->setAttribute($relation->getForeignKeyName(), null)->save();
    }
}
