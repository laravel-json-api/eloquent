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

namespace LaravelJsonApi\Eloquent\Fields\Concerns;

use InvalidArgumentException;
use function is_bool;

trait Countable
{

    /**
     * @var bool
     */
    private bool $countable = true;

    /**
     * @var bool|null
     */
    private ?bool $countableInRelationship = null;

    /**
     * @var string|null
     */
    private ?string $countAs = null;

    /**
     * @return $this
     */
    public function cannotCount(): self
    {
        $this->countable = false;

        return $this;
    }

    /**
     * Mark the relation as always having a "count" in the top-level meta of a relationship endpoint.
     *
     * @return $this
     */
    public function alwaysCountInRelationship(): self
    {
        $this->countableInRelationship = true;

        return $this;
    }

    /**
     * Mark the relation as never having a "count" in the top-level meta of a relationship endpoint.
     *
     * @return $this
     */
    public function dontCountInRelationship(): self
    {
        $this->countableInRelationship = false;

        return $this;
    }

    /**
     * Set an alias for the relationship count.
     *
     * @param string $name
     * @return $this
     */
    public function countAs(string $name): self
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->countAs = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isCountable(): bool
    {
        return $this->countable;
    }

    /**
     * @inheritDoc
     */
    public function isCountableInRelationship(): bool
    {
        if (!$this->isCountable()) {
            return false;
        }

        if (is_bool($this->countableInRelationship)) {
            return $this->countableInRelationship;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function withCountName(): string
    {
        if ($this->countAs) {
            return "{$this->relationName()} as {$this->countAs}";
        }

        return $this->relationName();
    }

    /**
     * @inheritDoc
     */
    public function keyForCount(): string
    {
        if ($this->countAs) {
            return $this->countAs;
        }

        return $this->relationName() . '_count';
    }
}
