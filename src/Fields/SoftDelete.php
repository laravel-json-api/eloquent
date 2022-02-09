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

namespace LaravelJsonApi\Eloquent\Fields;

use Illuminate\Support\Facades\Date;
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
     * @inheritDoc
     */
    protected function deserialize($value)
    {
        if (true === $this->boolean && (is_bool($value) || is_null($value))) {
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
