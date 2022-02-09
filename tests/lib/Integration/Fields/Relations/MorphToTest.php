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

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields\Relations;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class MorphToTest extends TestCase
{
    public function testName(): void
    {
        $relation = MorphTo::make('author');

        $this->assertSame('author', $relation->name());
        $this->assertSame('author', $relation->serializedFieldName());
        $this->assertSame('author', $relation->relationName());

        $relation = MorphTo::make('author', 'user');

        $this->assertSame('author', $relation->name());
        $this->assertSame('author', $relation->serializedFieldName());
        $this->assertSame('user', $relation->relationName());
    }

    public function testInverse(): void
    {
        $relation = MorphTo::make('author');

        $this->assertSame('authors', $relation->inverse());

        $relation = MorphTo::make('author', 'user');

        $this->assertSame('users', $relation->inverse());

        $this->assertSame($relation, $relation->inverseType('user-accounts'));

        $this->assertSame('user-accounts', $relation->inverse());
    }

    public function testToOneAndToMany(): void
    {
        $relation = MorphTo::make('author');

        $this->assertTrue($relation->toOne());
        $this->assertFalse($relation->toMany());
    }

    public function testItIsValidatedByDefault(): void
    {
        $relation = MorphTo::make('author');

        $this->assertTrue($relation->isValidated());
        $this->assertSame($relation, $relation->notValidated());
        $this->assertFalse($relation->isValidated());
        $this->assertSame($relation, $relation->mustValidate());
        $this->assertTrue($relation->isValidated());
    }

    public function testItDoesNotNeedToExist(): void
    {
        $relation = MorphTo::make('author');

        $this->assertFalse($relation->mustExist());
    }

    public function testUriName(): void
    {
        $relation = MorphTo::make('blogPost');

        $this->assertSame('blog-post', $relation->uriName());

        $this->assertSame($relation, $relation->withUriFieldName('blog_post'));

        $this->assertSame('blog_post', $relation->uriName());
    }

    public function testEagerLoadable(): void
    {
        $relation = MorphTo::make('author');

        $this->assertTrue($relation->isIncludePath());

        $this->assertSame($relation, $relation->cannotEagerLoad());

        $this->assertFalse($relation->isIncludePath());
    }

    public function testSparseField(): void
    {
        $relation = MorphTo::make('author');

        $this->assertTrue($relation->isSparseField());

        $this->assertSame($relation, $relation->notSparseField());

        $this->assertFalse($relation->isSparseField());
    }

    public function testFilterable(): void
    {
        $a = $this->createMock(Filter::class);
        $b = $this->createMock(Filter::class);

        $relation = MorphTo::make('author');

        $this->assertSame($relation, $relation->withFilters($a, $b));

        $this->assertSame([$a, $b], $relation->filters());
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = MorphTo::make('author')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = MorphTo::make('author')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }
}
