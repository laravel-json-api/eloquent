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
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\PolymorphicRelation;
use LogicException;
use function sprintf;

class MorphTo extends BelongsTo implements PolymorphicRelation
{

    use Polymorphic;

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
     * @inheritDoc
     */
    public function parse(?Model $model): ?object
    {
        if ($model) {
            return $this->schemaFor($model)->parser()->parseOne(
                $model
            );
        }

        return null;
    }

}
