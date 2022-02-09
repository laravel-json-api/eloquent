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

namespace LaravelJsonApi\Eloquent\Filters\Concerns;

use InvalidArgumentException;
use LogicException;
use function explode;
use function is_array;
use function is_string;

trait HasDelimiter
{

    /**
     * @var string|null
     */
    private ?string $delimiter = null;

    /**
     * If the filter accepts a string value, the delimiter to use to extract values.
     *
     * @param string $delimiter
     * @return $this
     */
    public function delimiter(string $delimiter): self
    {
        if (empty($delimiter)) {
            throw new InvalidArgumentException('Expecting a non-empty string delimiter.');
        }

        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Convert the provided value to an array.
     *
     * @param string|array|null $value
     * @return array
     */
    protected function toArray($value): array
    {
        if ($this->delimiter && is_string($value)) {
            return ('' !== $value) ? explode($this->delimiter, $value) : [];
        }

        if (is_array($value) || null === $value) {
            return $value ?? [];
        }

        throw new LogicException('Expecting filter value to be an array, or a string when a string delimiter is set.');
    }
}
