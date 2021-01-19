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
use LaravelJsonApi\Contracts\Pagination\Page as PageContract;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Responses\PaginatedResourceResponse;
use LaravelJsonApi\Core\Support\Arr;
use LaravelJsonApi\Eloquent\Pagination\Cursor\CursorPaginator;

class CursorPage implements PageContract
{

    /**
     * @var CursorPaginator
     */
    private CursorPaginator $paginator;

    /**
     * @var array|null
     */
    private ?array $queryParameters = null;

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
     * @var string|null
     */
    private ?string $metaKey = null;

    /**
     * @var string|null
     */
    private ?string $metaCase = null;

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
     * Use snake-case keys in the meta object.
     *
     * @return $this
     */
    public function withSnakeCaseMeta(): self
    {
        return $this->withMetaCase('snake');
    }

    /**
     * Use dash-case keys in the meta object.
     *
     * @return $this
     */
    public function withDashCaseMeta(): self
    {
        return $this->withMetaCase('dash');
    }

    /**
     * Use camel-case keys in the meta object.
     *
     * @return $this
     */
    public function withCamelCaseMeta(): self
    {
        return $this->withMetaCase(null);
    }

    /**
     * Set the key-case to use for meta.
     *
     * @param string|null $case
     * @return $this
     */
    public function withMetaCase(?string $case): self
    {
        if (in_array($case, [null, 'snake', 'dash'], true)) {
            $this->metaCase = $case;
            return $this;
        }

        throw new InvalidArgumentException('Invalid meta case: ' . $case ?? 'null');
    }

    /**
     * Nest page meta using the provided key.
     *
     * @param string|null $key
     * @return $this
     */
    public function withNestedMeta(?string $key = 'page'): self
    {
        $this->metaKey = $key;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function meta(): array
    {
        $meta = [
            'perPage' => $this->paginator->getPerPage(),
            'from' => $this->paginator->getFrom(),
            'to' => $this->paginator->getTo(),
            'hasMore' => $this->paginator->hasMorePages(),
        ];

        if ('snake' === $this->metaCase) {
            $meta = Arr::underscore($meta);
        } else if ('dash' === $this->metaCase) {
            $meta = Arr::dasherize($meta);
        }

        if ($this->metaKey) {
            return [$this->metaKey => $meta];
        }

        return $meta;
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
    public function withQuery(iterable $query): PageContract
    {
        $this->queryParameters = collect($query)->all();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function links(): Links
    {
        return new Links(...array_filter([
            $this->first(),
            $this->prev(),
            $this->next(),
            $this->last(),
        ]));
    }

    /**
     * @return Link
     */
    public function first(): Link
    {
        return new Link('first', $this->url([
            $this->limit => $this->paginator->getPerPage(),
        ]));
    }

    /**
     * @return Link|null
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
     * @return Link|null
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
     * @return Link|null
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
        $params = collect($this->queryParameters)
            ->put('page', collect($page)->sortKeys()->all())
            ->sortKeys()
            ->all();

        return $this->paginator->path() . '?' . Arr::query($params);
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
     * @param $request
     * @return PaginatedResourceResponse
     */
    public function prepareResponse($request): PaginatedResourceResponse
    {
        return new PaginatedResourceResponse($this);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this->prepareResponse($request)->toResponse($request);
    }

}
