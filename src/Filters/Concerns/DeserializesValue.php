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

use Closure;
use function filter_var;

trait DeserializesValue
{

    /**
     * @var Closure|null
     */
    private ?Closure $deserializer = null;

    /**
     * Use the supplied callback to deserialize the value.
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
     * Deserialize value as a boolean.
     *
     * @return $this
     */
    public function asBoolean(): self
    {
        $this->deserializeUsing(
            static fn($value) => filter_var($value, FILTER_VALIDATE_BOOL)
        );

        return $this;
    }

    /**
     * Deserialize the value.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function deserialize($value)
    {
        if ($this->deserializer) {
            return ($this->deserializer)($value);
        }

        return $value;
    }
}
