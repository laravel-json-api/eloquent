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

namespace LaravelJsonApi\Eloquent\Pagination\Cursor;

use InvalidArgumentException;

class Cursor
{

    /**
     * @var string|null
     */
    private ?string $before;

    /**
     * @var string|null
     */
    private ?string $after;

    /**
     * @var int|null
     */
    private ?int $limit;

    /**
     * Cursor constructor.
     *
     * @param string|null $before
     * @param string|null $after
     * @param int|null $limit
     */
    public function __construct(string $before = null, string $after = null, int $limit = null)
    {
        if (is_int($limit) && 1 > $limit) {
            throw new InvalidArgumentException('Expecting a limit that is 1 or greater.');
        }

        $this->before = $before ?: null;
        $this->after = $after ?: null;
        $this->limit = $limit;
    }

    /**
     * @return bool
     */
    public function isBefore(): bool
    {
        return !is_null($this->before);
    }

    /**
     * @return string|null
     */
    public function getBefore(): ?string
    {
        return $this->before;
    }

    /**
     * @return bool
     */
    public function isAfter(): bool
    {
        return !is_null($this->after) && !$this->isBefore();
    }

    /**
     * @return string|null
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Set a limit, if no limit is set on the cursor.
     *
     * @param int $limit
     * @return Cursor
     */
    public function withDefaultLimit(int $limit): self
    {
        if (is_null($this->limit)) {
            $copy = clone $this;
            $copy->limit = $limit;
            return $copy;
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

}
