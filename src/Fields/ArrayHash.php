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
use LaravelJsonApi\Core\Support\Arr;
use function asort;
use function is_null;
use function ksort;

class ArrayHash extends Attribute
{

    /**
     * @var Closure|null
     */
    private ?Closure $keys = null;

    /**
     * @var bool
     */
    private bool $sorted = false;

    /**
     * @var bool
     */
    private bool $sortKeys = false;

    /**
     * Create an array attribute.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return ArrayHash
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * Sort values when deserializing the array.
     *
     * @return $this
     */
    public function sorted(): self
    {
        $this->sorted = true;

        return $this;
    }

    /**
     * Sort values by their keys when deserializing the array.
     *
     * @return $this
     */
    public function sortedKeys(): self
    {
        $this->sortKeys = true;

        return $this;
    }

    /**
     * Camel-case array keys when deserializing the array.
     *
     * @return $this
     */
    public function camelize(): self
    {
        $this->keys = static fn($value) => Arr::camelize($value);

        return $this;
    }

    /**
     * @return $this
     */
    public function dasherize(): self
    {
        $this->keys = static fn($value) => Arr::dasherize($value);

        return $this;
    }

    /**
     * @return $this
     */
    public function snake(): self
    {
        return $this->underscore();
    }

    /**
     * @return $this
     */
    public function underscore(): self
    {
        $this->keys = static fn($value) => Arr::underscore($value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(object $model)
    {
        $value = parent::serialize($model);

        if ($value) {
            $this->sortValue($value);
        }

        return empty($value) ? null : $value;
    }

    /**
     * @inheritDoc
     */
    protected function deserialize($value)
    {
        $value = parent::deserialize($value);

        if ($value && $this->keys) {
            $value = ($this->keys)($value);
        }

        if ($value) {
            $this->sortValue($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if ((!is_null($value) && !is_array($value)) || (!empty($value) && !Arr::isAssoc($value))) {
            throw new \UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be an associative array.',
                $this->name()
            ));
        }
    }

    /**
     * @param $value
     * @return void
     */
    private function sortValue(array &$value): void
    {
        if ($this->sorted) {
            asort($value);
        } else if ($this->sortKeys) {
            ksort($value);
        }
    }

}
