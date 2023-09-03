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
use LaravelJsonApi\Validation\Rules\JsonNumber;
use UnexpectedValueException;

class Integer extends Attribute implements IsValidated
{
    use ValidatedWithListOfRules;

    /**
     * @var bool
     */
    private bool $acceptStrings = false;

    /**
     * Create a number attribute.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return self
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * @return $this
     */
    public function acceptStrings(): self
    {
        $this->acceptStrings = true;

        return $this;
    }

    /**
     * @return array
     */
    protected function defaultRules(): array
    {
        if ($this->acceptStrings) {
            return ['numeric', 'integer'];
        }

        return [(new JsonNumber())->onlyIntegers()];
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if (!$this->isInt($value)) {
            $expected = $this->acceptStrings ?
                'an integer or a numeric string that is an integer.' :
                'an integer.';

            throw new UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be ' . $expected,
                $this->name(),
            ));
        }
    }

    /**
     * Is the value a numeric value that this field accepts?
     *
     * @param mixed $value
     * @return bool
     */
    private function isInt(mixed $value): bool
    {
        if ($this->acceptStrings && is_string($value) && is_numeric($value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
        }

        if ($value === null || is_int($value)) {
            return true;
        }

        return false;
    }
}
