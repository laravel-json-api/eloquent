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

namespace LaravelJsonApi\Eloquent\Pagination;

use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Eloquent\Contracts\Proxy;
use Traversable;

final class ProxyPage implements Page
{

    /**
     * @var Page
     */
    private Page $page;

    /**
     * @var Proxy
     */
    private Proxy $proxy;

    /**
     * ProxyPage constructor.
     *
     * @param Page $page
     * @param Proxy $proxy
     */
    public function __construct(Page $page, Proxy $proxy)
    {
        $this->page = $page;
        $this->proxy = $proxy;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->proxy->iterator($this->page);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->page->count();
    }

    /**
     * @inheritDoc
     */
    public function meta(): array
    {
        return $this->page->meta();
    }

    /**
     * @inheritDoc
     */
    public function links(): Links
    {
        return $this->page->links();
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query): Page
    {
        $this->page->withQuery($query);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this->page->toResponse($request);
    }

}
