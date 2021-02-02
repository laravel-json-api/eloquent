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

namespace LaravelJsonApi\Eloquent\Fields\Concerns;

use Closure;
use Illuminate\Http\Request;

trait Hideable
{

    /**
     * @var Closure|bool
     */
    private $hidden = false;

    /**
     * Mark the field as hidden.
     *
     * @param Closure|null $callback
     * @return $this
     */
    public function hidden(Closure $callback = null): self
    {
        $this->hidden = $callback ?: true;

        return $this;
    }

    /**
     * Is the field hidden?
     *
     * @param Request|null $request
     * @return bool
     */
    public function isHidden($request): bool
    {
        if (is_callable($this->hidden)) {
            return true === ($this->hidden)($request);
        }

        return true === $this->hidden;
    }

    /**
     * Is the field not hidden?
     *
     * @param Request|null $request
     * @return bool
     */
    public function isNotHidden($request): bool
    {
        return !$this->isHidden($request);
    }
}
