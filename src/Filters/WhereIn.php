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

namespace LaravelJsonApi\Eloquent\Filters;

use InvalidArgumentException;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LogicException;
use function explode;
use function is_string;

class WhereIn implements Filter
{

    use Concerns\DeserializesValue;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $column;

    /**
     * @var string|null
     */
    private ?string $delimiter = null;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @param string|null $column
     * @return static
     */
    public static function make(string $name, string $column = null): self
    {
        return new static($name, $column);
    }

    /**
     * Where constructor.
     *
     * @param string $name
     * @param string|null $column
     */
    public function __construct(string $name, string $column = null)
    {
        $this->name = $name;
        $this->column = $column ?: $this->guessColumn();
    }

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
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        return $query->whereIn(
            $query->qualifyColumn($this->column()),
            $this->deserialize($value)
        );
    }

    /**
     * @return string
     */
    protected function column(): string
    {
        return $this->column;
    }

    /**
     * Deserialize the fitler value.
     *
     * @param string|array $value
     * @return array
     */
    protected function deserialize($value): array
    {
        if ($this->deserializer) {
            return ($this->deserializer)($value);
        }

        if ($this->delimiter && is_string($value)) {
            return explode($this->delimiter, $value);
        }

        if (is_array($value)) {
            return $value;
        }

        throw new LogicException('Expecting where in filter value to be an array, or a string if a string delimiter is set.');
    }

    /**
     * @return string
     */
    private function guessColumn(): string
    {
        return Str::underscore(
            Str::singular($this->name)
        );
    }

}
