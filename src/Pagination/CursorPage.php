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

namespace LaravelJsonApi\Eloquent\Pagination;

use InvalidArgumentException;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Pagination\AbstractPage;
use LaravelJsonApi\Eloquent\Pagination\Cursor\CursorPaginator;

class CursorPage extends AbstractPage
{

    /**
     * @var CursorPaginator
     */
    private CursorPaginator $paginator;

    /**
     * @var string
     */
    private string $before;

    /**
     * @var string
     */
    private string $after;

    /**
     * @var string
     */
    private string $limit;

    /**
     * Fluent constructor.
     *
     * @param CursorPaginator $paginator
     * @return CursorPage
     */
    public static function make(CursorPaginator $paginator): self
    {
        return new self($paginator);
    }

    /**
     * CursorPage constructor.
     *
     * @param CursorPaginator $paginator
     */
    public function __construct(CursorPaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Set the "after" parameter.
     *
     * @param string $key
     * @return $this
     */
    public function withAfterParam(string $key): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->after = $key;

        return $this;
    }

    /**
     * Set the "before" parameter.
     *
     * @param string $key
     * @return $this
     */
    public function withBeforeParam(string $key): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->before = $key;

        return $this;
    }

    /**
     * Set the "limit" parameter.
     *
     * @param string $key
     * @return $this
     */
    public function withLimitParam(string $key): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->limit = $key;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function first(): ?Link
    {
        return new Link('first', $this->url([
            $this->limit => $this->paginator->getPerPage(),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function prev(): ?Link
    {
        if ($this->paginator->isNotEmpty()) {
            return new Link('prev', $this->url([
                $this->before => $this->paginator->firstItem(),
                $this->limit => $this->paginator->getPerPage(),
            ]));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function next(): ?Link
    {
        if ($this->paginator->hasMorePages()) {
            return new Link('next', $this->url([
                $this->after => $this->paginator->lastItem(),
                $this->limit => $this->paginator->getPerPage(),
            ]));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function last(): ?Link
    {
        return null;
    }

    /**
     * @param array $page
     * @return string
     */
    public function url(array $page): string
    {
        return $this->paginator->path() . '?' . $this->stringifyQuery($page);
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        yield from $this->paginator;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return $this->paginator->count();
    }

    /**
     * @inheritDoc
     */
    protected function metaForPage(): array
    {
        return [
            'perPage' => $this->paginator->getPerPage(),
            'from' => $this->paginator->getFrom(),
            'to' => $this->paginator->getTo(),
            'hasMore' => $this->paginator->hasMorePages(),
        ];
    }
}
