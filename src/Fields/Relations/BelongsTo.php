<?php
/**
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
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use LaravelJsonApi\Core\Document\ResourceIdentifier;
use LaravelJsonApi\Core\Support\Str;
use LogicException;

class BelongsTo extends Relation
{

    /**
     * Create a to-one relation.
     *
     * @param string $fieldName
     * @param string|null $relation
     * @return BelongsTo
     */
    public static function make(string $fieldName, string $relation = null): BelongsTo
    {
        return new static($fieldName, $relation);
    }

    /**
     * @inheritDoc
     */
    public function toOne(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function mustExist(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        $relation = $model->{$this->relation()}();

        if (!$relation instanceof EloquentBelongsTo) {
            throw new LogicException('Expecting an Eloquent belongs-to relation.');
        }

        if ($related = $this->find($value)) {
            $relation->associate($related);
        } else {
            $relation->disassociate();
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
        $model->save();

        return $model->getRelation($this->relation());
    }

    /**
     * @param array|null $value
     * @return Model|null
     */
    protected function find(?array $value): ?Model
    {
        if (is_null($value)) {
            return null;
        }

        $identifier = ResourceIdentifier::fromArray($value);

        return $this
            ->schemas()
            ->schemaFor($identifier->type())
            ->repository()
            ->findOrFail($identifier->id());
    }

    /**
     * @inheritDoc
     */
    protected function guessInverse(): string
    {
        return Str::dasherize(
            Str::plural($this->name())
        );
    }

}
