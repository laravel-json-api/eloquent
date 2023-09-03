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

use Illuminate\Support\Facades\Date;
use LaravelJsonApi\Validation\Rules\DateTimeIso8601;
use LaravelJsonApi\Validation\Rules\JsonBoolean;
use UnexpectedValueException;
use function boolval;
use function is_bool;
use function is_null;
use function sprintf;

class SoftDelete extends DateTime
{
    /**
     * @var bool
     */
    private bool $boolean = false;

    /**
     * SoftDelete constructor.
     *
     * @param string $fieldName
     * @param string|null $column
     */
    public function __construct(string $fieldName, string $column = null)
    {
        parent::__construct($fieldName, $column);
        $this->unguarded();
    }

    /**
     * @return $this
     */
    public function asBoolean(): self
    {
        $this->boolean = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(object $model)
    {
        if (true === $this->boolean) {
            return boolval($model->{$this->column()});
        }

        return parent::serialize($model);
    }

    /**
     * @return array
     */
    protected function defaultRules(): array
    {
        return [
            'nullable',
            $this->boolean ? new JsonBoolean() : new DateTimeIso8601(),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function deserialize($value)
    {
        if (true === $this->boolean && (is_bool($value) || $value === null)) {
            return $this->parse($value ? Date::now() : null);
        }

        if (true === $this->boolean) {
            throw new UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be a boolean.',
                $this->name()
            ));
        }

        return parent::deserialize($value);
    }

}
