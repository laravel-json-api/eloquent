<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
use LaravelJsonApi\Eloquent\Contracts\FillableToOne;
use LaravelJsonApi\Eloquent\Fields\Concerns\IsReadOnly;
use LogicException;

class BelongsTo extends ToOne implements FillableToOne
{

    use IsReadOnly;

    /**
     * Create a belongs-to relation.
     *
     * @param string $fieldName
     * @param string|null $relation
     * @return static
     */
    public static function make(string $fieldName, string $relation = null): BelongsTo
    {
        return new static($fieldName, $relation);
    }

    /**
     * BelongsTo constructor.
     *
     * @param string $fieldName
     * @param string|null $relation
     */
    public function __construct(string $fieldName, string $relation = null)
    {
        parent::__construct($fieldName, $relation);
        $this->mustValidate();
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
    public function fill(Model $model, ?array $identifier): void
    {
        $relation = $model->{$this->relationName()}();

        if (!$relation instanceof EloquentBelongsTo) {
            throw new LogicException('Expecting an Eloquent belongs-to relation.');
        }

        if ($related = $this->find($identifier)) {
            $relation->associate($related);
        } else {
            $relation->disassociate();
        }
    }

    /**
     * @inheritDoc
     */
    public function associate(Model $model, ?array $identifier): ?Model
    {
        $this->fill($model, $identifier);
        $model->save();

        return $model->getRelation($this->relationName());
    }

}
