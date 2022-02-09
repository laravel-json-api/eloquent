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
use LaravelJsonApi\Core\Document\ResourceIdentifier;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Proxy;

abstract class ToOne extends Relation
{

    /**
     * @inheritDoc
     */
    public function toOne(): bool
    {
        return true;
    }

    /**
     * Parse a model for the relationship.
     *
     * @param Model|null $model
     * @return object|null
     */
    public function parse(?Model $model): ?object
    {
        if ($model) {
            return $this->schema()->parser()->parseOne($model);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function guessInverse(): string
    {
        return Str::dasherize(
            Str::plural($this->relationName())
        );
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

        $this->assertInverseType($identifier->type());

        $model = $this
            ->schemas()
            ->schemaFor($identifier->type())
            ->repository()
            ->findOrFail($identifier->id());

        if ($model instanceof Proxy) {
            return $model->toBase();
        }

        return $model;
    }
}
