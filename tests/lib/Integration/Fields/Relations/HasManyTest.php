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

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields\Relations;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class HasManyTest extends TestCase
{

    public function testName(): void
    {
        $relation = HasMany::make('tags');

        $this->assertSame('tags', $relation->name());
        $this->assertSame('tags', $relation->serializedFieldName());
        $this->assertSame('tags', $relation->relationName());

        $relation = HasMany::make('tags', 'blogTags');

        $this->assertSame('tags', $relation->name());
        $this->assertSame('tags', $relation->serializedFieldName());
        $this->assertSame('blogTags', $relation->relationName());
    }

    public function testInverse(): void
    {
        $relation = HasMany::make('tags');

        $this->assertSame('tags', $relation->inverse());

        $relation = HasMany::make('tags', 'blogTags');

        $this->assertSame('blog-tags', $relation->inverse());

        $this->assertSame($relation, $relation->inverseType('user-tags'));

        $this->assertSame('user-tags', $relation->inverse());
    }

    public function testToOneAndToMany(): void
    {
        $relation = HasMany::make('tags');

        $this->assertFalse($relation->toOne());
        $this->assertTrue($relation->toMany());
    }

    public function testItIsNotValidatedByDefault(): void
    {
        $relation = HasMany::make('tags');

        $this->assertFalse($relation->isValidated());
        $this->assertSame($relation, $relation->mustValidate());
        $this->assertTrue($relation->isValidated());
        $this->assertSame($relation, $relation->notValidated());
        $this->assertFalse($relation->isValidated());
    }

    public function testUriName(): void
    {
        $relation = HasMany::make('blogTags');

        $this->assertSame('blog-tags', $relation->uriName());

        $this->assertSame($relation, $relation->withUriFieldName('blog_tags'));

        $this->assertSame('blog_tags', $relation->uriName());
    }

    public function testEagerLoadable(): void
    {
        $relation = HasMany::make('tags');

        $this->assertTrue($relation->isIncludePath());

        $this->assertSame($relation, $relation->cannotEagerLoad());

        $this->assertFalse($relation->isIncludePath());
    }

    public function testSparseField(): void
    {
        $relation = HasMany::make('tags');

        $this->assertTrue($relation->isSparseField());

        $this->assertSame($relation, $relation->notSparseField());

        $this->assertFalse($relation->isSparseField());
    }

    public function testFilterable(): void
    {
        $a = $this->createMock(Filter::class);
        $b = $this->createMock(Filter::class);

        $relation = HasMany::make('tags');

        $this->assertSame($relation, $relation->withFilters($a, $b));

        $this->assertSame([$a, $b], $relation->filters());
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = HasMany::make('tags')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = HasMany::make('tags')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }

    public function testCountable(): void
    {
        $relation = HasMany::make('tags');

        $this->assertFalse($relation->isCountable());
        $this->assertFalse($relation->isCountableInRelationship());

        $this->assertSame($relation, $relation->canCount());
        $this->assertTrue($relation->isCountable());
        $this->assertTrue($relation->isCountableInRelationship());

        $this->assertSame('tags', $relation->withCountName());
        $this->assertSame('tags_count', $relation->keyForCount());

        $relation = HasMany::make('tags', 'blogTags');

        $this->assertSame('blogTags', $relation->withCountName());
        $this->assertSame('blog_tags_count', $relation->keyForCount());
    }

    public function testCountAs(): void
    {
        $relation = HasMany::make('tags')->countAs('total_tags');

        $this->assertSame('tags as total_tags', $relation->withCountName());
        $this->assertSame('total_tags', $relation->keyForCount());
    }

    public function testNotCountable(): void
    {
        $relation = HasMany::make('tags')->cannotCount();

        $this->assertFalse($relation->isCountable());
        $this->assertFalse($relation->isCountableInRelationship());

        $relation = HasMany::make('tags')->canCount()->dontCountInRelationship();

        $this->assertTrue($relation->isCountable());
        $this->assertFalse($relation->isCountableInRelationship());
    }

}
