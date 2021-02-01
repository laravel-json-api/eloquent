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
use LaravelJsonApi\Contracts\Schema\ID as IDContract;
use LaravelJsonApi\Core\Schema\Concerns\MatchesIds;
use LaravelJsonApi\Core\Schema\Concerns\Sortable;
use LaravelJsonApi\Eloquent\Contracts\Fillable;

class ID implements IDContract, Fillable
{

    use MatchesIds;
    use Sortable;

    /**
     * @var string|null
     */
    private ?string $column;

    /**
     * @var bool
     */
    private bool $clientIds = false;

    /**
     * Create an id field.
     *
     * @param string|null $column
     * @return ID
     */
    public static function make(string $column = null): self
    {
        return new self($column);
    }

    /**
     * IdField constructor.
     *
     * @param string|null $column
     */
    public function __construct(string $column = null)
    {
        $this->column = $column ?: null;
        $this->sortable();
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'id';
    }

    /**
     * @return string|null
     */
    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * @inheritDoc
     */
    public function key(): ?string
    {
        return $this->column();
    }

    /**
     * @inheritDoc
     */
    public function isSparseField(): bool
    {
        return false;
    }

    /**
     * Mark the ID as accepting client-generated ids.
     *
     * @param bool $bool
     * @return $this
     */
    public function clientIds(bool $bool = true): self
    {
        $this->clientIds = $bool;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function acceptsClientIds(): bool
    {
        return $this->clientIds;
    }

    /**
     * @inheritDoc
     */
    public function isReadOnly($request): bool
    {
        if ($this->acceptsClientIds()) {
            return !$request->isMethod('POST');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isNotReadOnly($request): bool
    {
        return !$this->isReadOnly($request);
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value): void
    {
        $column = $this->column() ?: $model->getRouteKeyName();

        $model->{$column} = $value;
    }

}
