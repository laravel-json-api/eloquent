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

namespace LaravelJsonApi\Eloquent\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Resources\Serializer\Attribute as SerializableContract;
use LaravelJsonApi\Contracts\Schema\Attribute as AttributeContract;
use LaravelJsonApi\Core\Schema\Concerns\SparseField;
use LaravelJsonApi\Eloquent\Contracts\EagerLoadableField;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\Selectable;
use LaravelJsonApi\Eloquent\Fields\Concerns\Hideable;
use LaravelJsonApi\Eloquent\Fields\Concerns\IsReadOnly;
use LaravelJsonApi\Eloquent\Fields\Concerns\OnRelated;
use LogicException;

class Map implements
    AttributeContract,
    EagerLoadableField,
    Fillable,
    Selectable,
    SerializableContract
{

    use Hideable;
    use OnRelated;
    use IsReadOnly;
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
     * Get the default eager load path for the attribute.
     *
     * @return string|string[]|null
     */
    public function with()
    {
       if ($this->related) {
           return $this->related;
       }

       $all = [];

       foreach ($this->map as $attribute) {
           if ($attribute instanceof EagerLoadableField) {
               $all = array_merge($all, Arr::wrap($attribute->with()));
           }
       }

       return array_values(array_unique($all));
    }

    /**
     * @inheritDoc
     */
    public function mustExist(): bool
    {
        if (!is_null($this->related)) {
            return true;
        }

        foreach ($this->map as $field) {
            if (($field instanceof Fillable) && $field->mustExist()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value, array $validatedData): void
    {
        $owner = $this->owner($model);

        if (is_null($value)) {
            $this->nullable($owner, $validatedData);
            return;
        }

        if (is_array($value)) {
            $this->values($owner, $value, $validatedData);
            return;
        }

        throw new LogicException('Expecting value for a map attribute to be an array or null.');
    }

    /**
     * @inheritDoc
     */
    public function serializedFieldName(): string
    {
        return $this->name();
    }

    /**
     * @inheritDoc
     */
    public function serialize(object $model)
    {
        $owner = $this->related ? $model->{$this->related} : $model;
        $values = [];

        /** We intentionally use a single loop for serialization efficiency. */
        if ($owner) {
            foreach ($this->map as $attr) {
                if ($attr instanceof SerializableContract) {
                    $values[$attr->serializedFieldName()] = $attr->serialize($owner);
                }
            }
        }

        ksort($values);

        return $values ?: null;
    }

    /**
     * Set all values to null.
     *
     * @param Model $model
     * @param array $validatedData
     * @return array
     */
    private function nullable(Model $model, array $validatedData): array
    {
        $results = [];

        if (false === $this->ignoreNull) {
            /** @var AttributeContract $attribute */
            foreach ($this->map as $attribute) {
                if ($attribute instanceof Fillable) {
                    $result = $attribute->fill($model, null, $validatedData);
                    $results = array_merge($results, Arr::wrap($result));
                    continue;
                }

                throw new LogicException(sprintf(
                    'Map attribute %s.%s is not fillable.',
                    $this->name(),
                    $attribute->name()
                ));
            }
        }

        return $results;
    }

    /**
     * Set values using the provided array.
     *
     * @param Model $model
     * @param array $values
     * @param array $validatedData
     * @return array
     */
    private function values(Model $model, array $values, array $validatedData): array
    {
        $results = [];

        foreach ($values as $key => $value) {
            $attr = $this->map[$key] ?? null;

            if ($attr && $attr instanceof Fillable) {
                $result = $attr->fill($model, $value, $validatedData);
                $results = array_merge($results, Arr::wrap($result));
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

        return $results;
    }

}
