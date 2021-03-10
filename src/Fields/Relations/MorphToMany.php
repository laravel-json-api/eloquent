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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Contracts\Schema\PolymorphicRelation;
use IteratorAggregate;
use UnexpectedValueException;

class MorphToMany extends ToMany implements PolymorphicRelation, IteratorAggregate
{

    /**
     * @var array
     */
    private array $relations;

    /**
     * @param string $fieldName
     * @param array $relations
     * @return static
     */
    public static function make(string $fieldName, array $relations): self
    {
        return new self($fieldName, $relations);
    }

    /**
     * MorphMany constructor.
     *
     * @param string $fieldName
     * @param array $relations
     */
    public function __construct(string $fieldName, array $relations)
    {
        if (2 > count($relations)) {
            throw new InvalidArgumentException('Expecting morph-many to have more than one relation.');
        }

        parent::__construct($fieldName);
        $this->relations = $relations;
    }

    /**
     * @inheritDoc
     */
    public function inverseTypes(): array
    {
        return collect($this->relations)
            ->map(fn(ToMany $relation) => $relation->inverse())
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function withSchemas(Container $container): void
    {
        parent::withSchemas($container);

        foreach ($this as $relation) {
            $relation->withSchemas($container);
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): iterable
    {
        foreach ($this->relations as $relation) {
            if ($relation instanceof self) {
                throw new UnexpectedValueException(
                    'Cannot have a JSON:API morph-to-many relation within a morph-to-many relation.'
                );
            }

            if ($relation instanceof Relation) {
                yield $relation;
                continue;
            }

            throw new UnexpectedValueException(
                'JSON:API morph-to-many relation expects to receive JSON:API relation objects.'
            );
        }
    }

    /**
     * Get the value of the relationship from the supplied model.
     *
     * @param object $model
     * @return Collection
     */
    public function value(object $model): Collection
    {
        return collect($this->relations)
            ->map(fn(Relation $relation) => $model->{$relation->relationName()})
            ->flatten();
    }

}
