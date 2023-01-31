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

namespace LaravelJsonApi\Eloquent\Tests\Unit\Pagination;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Pagination\MultiPagination;
use PHPUnit\Framework\TestCase;

class MultiPaginationTest extends TestCase
{
    public function testKeys(): void
    {
        $paginator1 = $this->createMock(Paginator::class);
        $paginator1->expects($this->once())->method('keys')->willReturn(['number', 'size']);

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2->expects($this->once())->method('keys')->willReturn(['before', 'after', 'limit']);

        $paginator3 = $this->createMock(Paginator::class);
        $paginator3->expects($this->once())->method('keys')->willReturn(['number', 'chunk']);

        $paginator = new MultiPagination(
            $paginator1,
            $paginator2,
            $paginator3,
        );

        $this->assertSame($expected = [
            'number',
            'size',
            'before',
            'after',
            'limit',
            'chunk',
        ], $paginator->keys());
        $this->assertSame($expected, $paginator->keys());
    }

    public function testWithColumns(): void
    {
        $columns = ['foo', 'bar', 'baz'];

        $paginator1 = $this->createMock(Paginator::class);
        $paginator1
            ->expects($this->once())
            ->method('withColumns')
            ->with($this->identicalTo($columns))
            ->willReturnSelf();

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2
            ->expects($this->once())
            ->method('withColumns')
            ->with($this->identicalTo($columns))
            ->willReturnSelf();

        $paginator = new MultiPagination($paginator1, $paginator2);
        $actual = $paginator->withColumns($columns);

        $this->assertSame($paginator, $actual);
    }

    public function testWithKeyName(): void
    {
        $key = 'blah';

        $paginator1 = $this->createMock(Paginator::class);
        $paginator1
            ->expects($this->once())
            ->method('withKeyName')
            ->with($this->identicalTo($key))
            ->willReturnSelf();

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2
            ->expects($this->once())
            ->method('withKeyName')
            ->with($this->identicalTo($key))
            ->willReturnSelf();

        $paginator = new MultiPagination($paginator1, $paginator2);
        $actual = $paginator->withKeyName($key);

        $this->assertSame($paginator, $actual);
    }

    public function testItQueriesPaginatorBasedOnKeys(): void
    {
        $query = $this->createMock(Builder::class);
        $page = ['number' => 2, 'chunk' => 3];

        $paginator1 = $this->createMock(Paginator::class);
        $paginator1->method('keys')->willReturn(['number', 'size']);
        $paginator1->expects($this->never())->method('paginate');

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2->method('keys')->willReturn(['before', 'after', 'limit']);
        $paginator2->expects($this->never())->method('paginate');

        $paginator3 = $this->createMock(Paginator::class);
        $paginator3->method('keys')->willReturn(['number', 'chunk']);
        $paginator3
            ->expects($this->once())
            ->method('paginate')
            ->with($this->identicalTo($query), $this->identicalTo($page))
            ->willReturn($expected = $this->createMock(Page::class));

        $paginator = new MultiPagination(
            $paginator1,
            $paginator2,
            $paginator3,
        );

        $actual = $paginator->paginate($query, $page);

        $this->assertSame($expected, $actual);
    }

    public function testItQueriesPaginatorBasedOnSomeKeys(): void
    {
        $query = $this->createMock(Builder::class);
        $page = ['after' => 'some-id', 'limit' => 10];

        $paginator1 = $this->createMock(Paginator::class);
        $paginator1->method('keys')->willReturn(['number', 'limit']);
        $paginator1->expects($this->never())->method('paginate');

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2->method('keys')->willReturn(['before', 'after', 'limit']);
        $paginator2
            ->expects($this->once())
            ->method('paginate')
            ->with($this->identicalTo($query), $this->identicalTo($page))
            ->willReturn($expected = $this->createMock(Page::class));

        $paginator3 = $this->createMock(Paginator::class);
        $paginator3->method('keys')->willReturn(['number', 'chunk']);
        $paginator3->expects($this->never())->method('paginate');

        $paginator = new MultiPagination(
            $paginator1,
            $paginator2,
            $paginator3,
        );

        $actual = $paginator->paginate($query, $page);

        $this->assertSame($expected, $actual);
    }

    /**
     * If the page keys match multiple paginators, we'll use the first matching paginator.
     *
     * @return void
     */
    public function testItUsesFirstMatchingPaginatorWhenNoneAreConclusive(): void
    {
        $query = $this->createMock(Builder::class);
        $page = ['number' => 1, 'after' => 'some-id'];

        $paginator1 = $this->createMock(Paginator::class);
        $paginator1->method('keys')->willReturn(['foo', 'bar']);
        $paginator1->expects($this->never())->method('paginate');

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2->method('keys')->willReturn(['before', 'after', 'limit']);
        $paginator2
            ->expects($this->once())
            ->method('paginate')
            ->with($this->identicalTo($query), $this->identicalTo($page))
            ->willReturn($expected = $this->createMock(Page::class));

        $paginator3 = $this->createMock(Paginator::class);
        $paginator3->method('keys')->willReturn(['number', 'size']);
        $paginator3->expects($this->never())->method('paginate');

        $paginator = new MultiPagination(
            $paginator1,
            $paginator2,
            $paginator3,
        );

        $actual = $paginator->paginate($query, $page);

        $this->assertSame($expected, $actual);
    }

    public function testItHasInconclusivePageParameters(): void
    {
        $query = $this->createMock(Builder::class);
        $page = ['foo' => 'bar', 'baz' => 'bat'];

        $paginator1 = $this->createMock(Paginator::class);
        $paginator1->method('keys')->willReturn(['number', 'size']);
        $paginator1->expects($this->never())->method('paginate');

        $paginator2 = $this->createMock(Paginator::class);
        $paginator2->method('keys')->willReturn(['before', 'after', 'limit']);
        $paginator2->expects($this->never())->method('paginate');

        $paginator = new MultiPagination($paginator1, $paginator2);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Could not determine which paginator to use. ' .
            'Use validation to ensure the client provides query parameters that match at least one paginator. ' .
            'Keys received: foo,baz',
        );

        $paginator->paginate($query, $page);
    }
}