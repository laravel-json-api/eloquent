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

namespace LaravelJsonApi\Eloquent\Tests\Unit\Query;

use Illuminate\Support\Arr;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Query\SortFields;
use LaravelJsonApi\Eloquent\Query\CountablePaths;
use LaravelJsonApi\Eloquent\Query\ExtendedQueryParameters;
use PHPUnit\Framework\TestCase;

class ExtendedQueryParametersTest extends TestCase
{


    public function test(): ExtendedQueryParameters
    {
        $parameters = ExtendedQueryParameters::cast($value = [
            'bazbat' => 'foobar',
            'fields' => [
                'comments' => 'user,content',
                'posts' => 'author,createdAt,synopsis,title',
            ],
            'filter' => ['foo' => 'bar', 'baz' => 'bat'],
            'foobar' => 'bazbat',
            'include' => 'author,comments.user',
            'page' => ['number' => '1', 'size' => '25'],
            'sort' => '-createdAt,id',
            'withCount' => 'comments,tags',
        ]);

        $this->assertInstanceOf(ExtendedQueryParameters::class, $parameters);
        $this->assertEquals(FieldSets::fromArray($value['fields']), $parameters->sparseFieldSets());
        $this->assertEquals($value['filter'], $parameters->filter());
        $this->assertEquals(IncludePaths::fromString($value['include']), $parameters->includePaths());
        $this->assertEquals($value['page'], $parameters->page());
        $this->assertEquals(SortFields::fromString($value['sort']), $parameters->sortFields());
        $this->assertEquals(new CountablePaths('comments', 'tags'), $parameters->countable());
        $this->assertEquals([
            'foobar' => 'bazbat',
            'bazbat' => 'foobar',
        ], $parameters->unrecognisedParameters());

        $arr = $value;
        $arr['include'] = ['author', 'comments.user'];
        $arr['sort'] = ['-createdAt', 'id'];
        $arr['withCount'] = ['comments', 'tags'];

        $this->assertSame($arr, $parameters->toArray());
        $this->assertSame($value, $parameters->toQuery());
        $this->assertSame(Arr::query($value), (string) $parameters);

        return $parameters;
    }

    /**
     * @param ExtendedQueryParameters $expected
     * @depends test
     */
    public function testCastBaseQueryParameters(ExtendedQueryParameters $expected): void
    {
        $base = QueryParameters::fromArray($expected->toQuery());

        $this->assertSame([
            'bazbat' => 'foobar',
            'foobar' => 'bazbat',
            'withCount' => 'comments,tags',
        ], $base->unrecognisedParameters());

        $this->assertEquals(
            $expected,
            $actual = ExtendedQueryParameters::cast($base)
        );

        $this->assertEquals($expected->countable(), $actual->countable());
    }
}
