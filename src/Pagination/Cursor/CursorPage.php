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

namespace LaravelJsonApi\Eloquent\Pagination\Cursor;

use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Pagination\AbstractPage;

class CursorPage extends AbstractPage
{
    private CursorPaginator $paginator;

    private string $before;

    private string $after;

    private string $limit;

    /**
     * CursorPage constructor.
     */
    public function __construct(CursorPaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Fluent constructor.
     */
    public static function make(CursorPaginator $paginator): self
    {
        return new self($paginator);
    }

    /**
     * Set the "after" parameter.
     *
     * @return $this
     */
    public function withAfterParam(string $key): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->after = $key;

        return $this;
    }

    /**
     * Set the "before" parameter.
     *
     * @return $this
     */
    public function withBeforeParam(string $key): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->before = $key;

        return $this;
    }

    /**
     * Set the "limit" parameter.
     *
     * @return $this
     */
    public function withLimitParam(string $key): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->limit = $key;

        return $this;
    }

    public function first(): ?Link
    {
        return new Link('first', $this->url([
            $this->limit => $this->paginator->getPerPage(),
        ]));
    }

    public function prev(): ?Link
    {
        if ($this->paginator->isNotEmpty() && $this->paginator->hasPrev()) {
            return new Link('prev', $this->url([
                $this->before => $this->paginator->firstItem(),
                $this->limit => $this->paginator->getPerPage(),
            ]));
        }

        return null;
    }

    public function next(): ?Link
    {
        if ($this->paginator->isNotEmpty() && $this->paginator->hasNext()) {
            return new Link('next', $this->url([
                $this->after => $this->paginator->lastItem(),
                $this->limit => $this->paginator->getPerPage(),
            ]));
        }

        return null;
    }

    public function last(): ?Link
    {
        return null;
    }

    /**
     * @param array<string,mixed> $page
     */
    public function url(array $page): string
    {
        return $this->paginator->path() . '?' . $this->stringifyQuery($page);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->paginator;
    }

    public function count(): int
    {
        return $this->paginator->count();
    }

    /**
     * @return array<string,mixed>
     */
    protected function metaForPage(): array
    {
        $meta = [
            'perPage' => $this->paginator->getPerPage(),
            'from' => $this->paginator->getFrom(),
            'to' => $this->paginator->getTo(),
            'hasMore' => $this->paginator->hasMorePages(),
        ];
        $total = $this->paginator->getTotal();
        if ($total !== null) {
            $meta['total'] = $total;
        }

        return $meta;
    }
}
