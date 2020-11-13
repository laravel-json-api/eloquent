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
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\PolymorphicRelation;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;
use UnexpectedValueException;
use function sprintf;

class MorphTo extends BelongsTo implements PolymorphicRelation
{

    /**
     * @var string[]
     */
    private array $types = [];

    /**
     * Set the inverse resource types.
     *
     * @param string ...$types
     * @return $this
     */
    public function types(string ...$types): self
    {
        if (2 > count($types)) {
            throw new InvalidArgumentException('Expecting at least two resource types.');
        }

        $this->types = $types;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inverseTypes(): array
    {
        if (empty($this->types)) {
            throw new LogicException(sprintf(
                'No inverse resource types have been set on morph-to relation %s.',
                $this->name()
            ));
        }

        return $this->types;
    }

    /**
     * @param Model $model
     * @return Schema
     */
    public function schemaFor(Model $model): Schema
    {
        $expected = get_class($model);

        foreach ($this->types as $type) {
            $schema = $this->schemas()->schemaFor($type);

            if ($expected === $schema->model()) {
                if ($schema instanceof Schema) {
                    return $schema;
                }

                throw new LogicException(sprintf(
                    'Expecting schema for resource type %s to be an Eloquent schema.',
                    $type
                ));
            }
        }

        throw new UnexpectedValueException(sprintf(
            'Model %s is not valid for morph-to relation %s.',
            $expected,
            $this->name()
        ));
    }

    /**
     * @param string $type
     * @return void
     */
    protected function assertInverseType(string $type): void
    {
        $expected = collect($this->inverseTypes());

        if (!$expected->containsStrict($type)) {
            throw new LogicException(sprintf(
                'Resource type %s is not a valid inverse resource type for relation %s: expecting %s.',
                $type,
                $this->name(),
                $expected->implode(', ')
            ));
        }
    }
}
