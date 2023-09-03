<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Fields\ValidatedWithListOfRules;
use LaravelJsonApi\Validation\Rules\JsonBoolean;

class Boolean extends Attribute implements IsValidated
{
    use ValidatedWithListOfRules;

    /**
     * Create a boolean attribute.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return Boolean
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * @return array
     */
    protected function defaultRules(): array
    {
        return [new JsonBoolean()];
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if ($value !== null && !is_bool($value)) {
            throw new \UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be a boolean.',
                $this->name()
            ));
        }
    }
}
