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

namespace LaravelJsonApi\Eloquent\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Schema\Attribute as AttributeContract;
use LaravelJsonApi\Core\Schema\Concerns\Sortable;
use LaravelJsonApi\Core\Schema\Concerns\SparseField;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\Selectable;
use LaravelJsonApi\Eloquent\Contracts\Sortable as SortableContract;

abstract class Attribute implements AttributeContract, Fillable, Selectable, SortableContract
{

    use Sortable;
    use Concerns\ReadOnly;
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
     * @var bool
     */
    private bool $force = false;

    /**
     * @var Closure|null
     */
    private ?Closure $deserializer = null;

    /**
     * Attribute constructor.
     *
     * @param string $fieldName
     * @param string|null $column
     */
    public function __construct(string $fieldName, string $column = null)
    {
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
     * @param Closure $deserializer
     * @return $this
     */
    public function deserializeUsing(Closure $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * @return $this
     */
    public function forceFill(): self
    {
        $this->force = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        $value = $this->deserialize($model, $value);

        if ($this->force) {
            $model->forceFill([$this->column() => $value]);
            return;
        }

        $model->fill([$this->column() => $value]);
    }

    /**
     * @inheritDoc
     */
    public function sort($query, bool $ascending)
    {
        return $query->orderBy(
            $this->column(),
            $ascending ? 'asc' : 'desc'
        );
    }

    /**
     * Convert the JSON value for this field for setting on the provided model.
     *
     * @param Model $model
     * @param mixed $value
     * @return mixed
     */
    protected function deserialize(Model $model, $value)
    {
        if ($this->deserializer) {
            return ($this->deserializer)($model, $value);
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
