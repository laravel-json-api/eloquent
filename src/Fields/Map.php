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

namespace LaravelJsonApi\Eloquent\Fields;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Attribute as AttributeContract;
use LaravelJsonApi\Core\Schema\Concerns\SparseField;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\Selectable;
use LaravelJsonApi\Eloquent\Fields\Concerns\ReadOnly;
use LogicException;

class Map implements AttributeContract, Selectable, Fillable
{

    use ReadOnly;
    use SparseField;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array
     */
    private array $map;

    /**
     * @var bool
     */
    private bool $ignoreNull = false;

    /**
     * Create a map attribute.
     *
     * @param string $fieldName
     * @param array $map
     * @return Map
     */
    public static function make(string $fieldName, array $map): self
    {
        return new self($fieldName, $map);
    }

    /**
     * Map constructor.
     *
     * @param string $name
     * @param array $map
     */
    public function __construct(string $name, array $map)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Expecting a non-empty string field name.');
        }

        $this->name = $name;
        $this->map = collect($map)
            ->keyBy(static fn(AttributeContract $attr) => $attr->name())
            ->sortKeys()
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function columnsForField(): array
    {
        return collect($this->map)
            ->map(static fn ($attr) => $attr instanceof Selectable ? $attr->columnsForField() : [])
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Do not fill any values if the value is null.
     *
     * @return $this
     */
    public function ignoreNull(): self
    {
        $this->ignoreNull = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        if (is_null($value)) {
            $this->nullable($model);
            return;
        }

        if (is_array($value)) {
            $this->values($model, $value);
            return;
        }

        throw new LogicException('Expecting value for a map attribute to be an array or null.');
    }

    /**
     * Set all values to null.
     *
     * @param Model $model
     * @return void
     */
    private function nullable(Model $model): void
    {
        if (false === $this->ignoreNull) {
            /** @var AttributeContract $attribute */
            foreach ($this->map as $attribute) {
                if ($attribute instanceof Fillable) {
                    $attribute->fill($model, null);
                    continue;
                }

                throw new LogicException(sprintf(
                    'Map attribute %s.%s is not fillable.',
                    $this->name(),
                    $attribute->name()
                ));
            }
        }
    }

    /**
     * Set values using the provided array.
     *
     * @param Model $model
     * @param array $values
     */
    private function values(Model $model, array $values): void
    {
        foreach ($values as $key => $value) {
            $attr = $this->map[$key] ?? null;

            if ($attr && $attr instanceof Fillable) {
                $attr->fill($model, $value);
                continue;
            }

            if ($attr) {
                throw new LogicException(sprintf(
                    'Map attribute %s.%s is not fillable.',
                    $this->name(),
                    $attr->name()
                ));
            }
        }
    }

}
