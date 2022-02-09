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

trait HasOperator
{

    /**
     * @var string
     */
    private string $operator;

    /**
     * @return $this
     */
    public function eq(): self
    {
        return $this->using('=');
    }

    /**
     * @return $this
     */
    public function gt(): self
    {
        return $this->using('>');
    }

    /**
     * @return $this
     */
    public function gte(): self
    {
        return $this->using('>=');
    }

    /**
     * @return $this
     */
    public function lt(): self
    {
        return $this->using('<');
    }

    /**
     * @return $this
     */
    public function lte(): self
    {
        return $this->using('<=');
    }

    /**
     * Use the provided operator for the filter.
     *
     * @param string $operator
     * @return $this
     */
    public function using(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * @return string
     */
    public function operator(): string
    {
        return $this->operator;
    }
}
