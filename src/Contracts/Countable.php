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

namespace LaravelJsonApi\Eloquent\Contracts;

use LaravelJsonApi\Contracts\Implementations\Countable\CountableField;

interface Countable extends CountableField
{

    /**
     * Set the countable flag on the relationship.
     *
     * @param bool $countable
     * @return $this
     */
    public function countable(bool $countable): self;

    /**
     * Should the relationship count be loaded for top-level meta in a relationship endpoint?
     *
     * @return bool
     */
    public function isCountableInRelationship(): bool;

    /**
     * Get the name to use when calling `withCount`.
     *
     * @return string
     */
    public function withCountName(): string;

    /**
     * Get the key to use to retrieve the count value from the model.
     *
     * @return string
     */
    public function keyForCount(): string;
}
