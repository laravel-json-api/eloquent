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

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Attribute as AttributeContract;
use LaravelJsonApi\Core\Schema\Concerns\Sortable;
use LaravelJsonApi\Core\Schema\Concerns\SparseField;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\Selectable;
use LaravelJsonApi\Eloquent\Contracts\Sortable as SortableContract;
use LaravelJsonApi\Contracts\Resources\Serializer\Attribute as SerializableContract;

abstract class Attribute implements AttributeContract, Fillable, Selectable, SortableContract, SerializableContract
{

    use Concerns\Hideable;
    use Concerns\ReadOnly;
    use Sortable;
    use SparseField;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $column;

    /**
     * @var Closure|null
     */
    private ?Closure $deserializer = null;

    /**
     * @var Closure|null
     */
    private ?Closure $serializer = null;

    /**
     * @var Closure|null
     */
    private ?Closure $hydrator = null;

    /**
     * @var bool
     */
    private bool $force = false;

    /**
     * Assert that the attribute JSON value is as expected.
     *
     * @param $value
     * @return void
     */
    abstract protected function assertValue($value): void;

    /**
     * Attribute constructor.
     *
     * @param string $fieldName
     * @param string|null $column
     */
    public function __construct(string $fieldName, string $column = null)
    {
        if (empty($fieldName)) {
            throw new InvalidArgumentException('Expecting a non-empty string field name.');
        }

        $this->name = $fieldName;
        $this->column = $column ?: $this->guessColumn();
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
    public function serializedFieldName(): string
    {
        return $this->name();
    }

    /**
     * @return string
     */
    public function column(): string
    {
        return $this->column;
    }

    /**
     * @inheritDoc
     */
    public function columnsForField(): array
    {
        return [$this->column()];
    }

    /**
     * Customise the hydration of the model attribute.
     *
     * @param Closure $hydrator
     * @return $this
     */
    public function fillUsing(Closure $hydrator): self
    {
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * Ignore mass-assignment and always fill the attribute.
     *
     * @return $this
     */
    public function unguarded(): self
    {
        $this->force = true;

        return $this;
    }

    /**
     * Customise deserialization of the value.
     *
     * @param Closure $deserializer
     * @return $this
     */
    public function deserializeUsing(Closure $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * @param Closure $serializer
     * @return $this
     */
    public function serializeUsing(Closure $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        $value = $this->deserialize($value);

        if ($this->hydrator) {
            ($this->hydrator)($model, $this->column(), $value);
            return;
        }

        if (false === $this->force) {
            $model->fill([$this->column() => $value]);
            return;
        }

        $model->{$this->column()} = $value;
    }

    /**
     * @inheritDoc
     */
    public function sort($query, string $direction = 'asc')
    {
        return $query->orderBy(
            $query->getModel()->qualifyColumn($this->column()),
            $direction
        );
    }

    /**
     * @inheritDoc
     */
    public function serialize(object $model)
    {
        $value = $model->{$this->column()};

        if ($this->serializer) {
            return ($this->serializer)($value);
        }

        return $value;
    }

    /**
     * Convert the JSON value for this field.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function deserialize($value)
    {
        $this->assertValue($value);

        if ($this->deserializer) {
            return ($this->deserializer)($value);
        }

        return $value;
    }

    /**
     * @return string
     */
    private function guessColumn(): string
    {
        return Str::underscore($this->name());
    }

}
