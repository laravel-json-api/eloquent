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

use Illuminate\Support\Enumerable;
use UnexpectedValueException;
use function is_array;

trait DeserializesToArray
{
    use DeserializesValue;

    /**
     * @param mixed $value
     * @return array
     */
    protected function toArray($value): array
    {
        $values = $this->deserialize($value);

        if ($values instanceof Enumerable) {
            return $values->all();
        }

        if (is_array($values) || null === $values) {
            return $values ?? [];
        }

        throw new UnexpectedValueException('Expecting filter value to deserialize to an array.');
    }
}
