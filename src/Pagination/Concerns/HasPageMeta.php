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

namespace LaravelJsonApi\Eloquent\Pagination\Concerns;

trait HasPageMeta
{

    /**
     * @var string|null
     */
    private ?string $metaKey;

    /**
     * @var string|null
     */
    private ?string $metaCase = null;

    /**
     * @var bool
     */
    private bool $hasMeta = true;

    /**
     * Set the key for the paging meta.
     *
     * Use this to 'nest' the paging meta in a sub-key of the JSON API document's top-level meta object.
     * A string sets the key to use for nesting. Use `null` to indicate no nesting.
     *
     * @param string|null $key
     * @return $this
     */
    public function withMetaKey(?string $key): self
    {
        $this->metaKey = $key ?: null;

        return $this;
    }

    /**
     * Mark the paginator as not nesting page meta.
     *
     * @return $this
     */
    public function withoutNestedMeta(): self
    {
        return $this->withMetaKey(null);
    }

    /**
     * Use snake-case meta keys.
     *
     * @return $this
     */
    public function withSnakeCaseMeta(): self
    {
        $this->metaCase = 'snake';

        return $this;
    }

    /**
     * Use dash-case meta keys.
     *
     * @return $this
     */
    public function withDashCaseMeta(): self
    {
        $this->metaCase = 'dash';

        return $this;
    }

    /**
     * Use camel-case meta keys.
     *
     * @return $this
     */
    public function withCamelCaseMeta(): self
    {
        $this->metaCase = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutMeta(): self
    {
        $this->hasMeta = false;

        return $this;
    }
}
