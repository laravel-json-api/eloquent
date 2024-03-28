<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields\Relations;

use App\Schemas\ApprovedPivot;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;
use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Rules\HasMany;
use LaravelJsonApi\Validation\Rules\JsonArray;

class BelongsToManyTest extends TestCase
{

    public function testName(): void
    {
        $relation = BelongsToMany::make('tags');

        $this->assertSame('tags', $relation->name());
        $this->assertSame('tags', $relation->serializedFieldName());
        $this->assertSame('tags', $relation->relationName());

        $relation = BelongsToMany::make('tags', 'blogTags');

        $this->assertSame('tags', $relation->name());
        $this->assertSame('tags', $relation->serializedFieldName());
        $this->assertSame('blogTags', $relation->relationName());
    }

    public function testInverse(): void
    {
        $relation = BelongsToMany::make('tags');

        $this->assertSame('tags', $relation->inverse());

        $relation = BelongsToMany::make('tags', 'blogTags');

        $this->assertSame('blog-tags', $relation->inverse());

        $this->assertSame($relation, $relation->inverseType('user-tags'));

        $this->assertSame('user-tags', $relation->inverse());
    }

    public function testToOneAndToMany(): void
    {
        $relation = BelongsToMany::make('tags');

        $this->assertFalse($relation->toOne());
        $this->assertTrue($relation->toMany());
    }

    public function testItIsNotValidatedByDefault(): void
    {
        $relation = BelongsToMany::make('tags');

        $this->assertFalse($relation->isValidated());
        $this->assertSame($relation, $relation->mustValidate());
        $this->assertTrue($relation->isValidated());
        $this->assertSame($relation, $relation->notValidated());
        $this->assertFalse($relation->isValidated());
    }

    public function testValidationRules(): void
    {
        $relation = BelongsToMany::make('tags')
            ->creationRules(['*.type' => 'foo'])
            ->updateRules(['*.type' => 'bar']);

        $this->assertInstanceOf(IsValidated::class, $relation);
        $this->assertEquals([
            '.' => [new JsonArray(), new HasMany($relation)],
            '*' => ['array:type,id'],
            '*.type' => ['foo'],
        ], $relation->rulesForCreation(null));
        $this->assertEquals([
            '.' => [new JsonArray(), new HasMany($relation)],
            '*' => ['array:type,id'],
            '*.type' => ['bar'],
        ], $relation->rulesForUpdate(null, new \stdClass()));
    }

    public function testUriName(): void
    {
        $relation = BelongsToMany::make('blogTags');

        $this->assertSame('blog-tags', $relation->uriName());

        $this->assertSame($relation, $relation->withUriFieldName('blog_tags'));

        $this->assertSame('blog_tags', $relation->uriName());
    }

    public function testEagerLoadable(): void
    {
        $relation = BelongsToMany::make('tags');

        $this->assertTrue($relation->isIncludePath());

        $this->assertSame($relation, $relation->cannotEagerLoad());

        $this->assertFalse($relation->isIncludePath());
    }

    public function testSparseField(): void
    {
        $relation = BelongsToMany::make('tags');

        $this->assertTrue($relation->isSparseField());

        $this->assertSame($relation, $relation->notSparseField());

        $this->assertFalse($relation->isSparseField());
    }

    public function testFilterable(): void
    {
        $a = $this->createMock(Filter::class);
        $b = $this->createMock(Filter::class);

        $relation = BelongsToMany::make('tags');

        $this->assertSame($relation, $relation->withFilters($a, $b));

        $this->assertSame([$a, $b], iterator_to_array($relation->filters()));
    }

    public function testFilterableWithPivot(): void
    {
        $a = $this->createMock(Filter::class);
        $b = $this->createMock(Filter::class);
        $c = $this->createMock(Filter::class);

        $pivot = $this->createMock(ApprovedPivot::class);
        $pivot->method('filters')->willReturn([$c]);

        $relation = BelongsToMany::make('tags');

        $this->assertSame($relation, $relation->fields($pivot));

        $this->assertSame($relation, $relation->withFilters($a, $b));

        $this->assertSame([$a, $b, $c], iterator_to_array($relation->filters()));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = BelongsToMany::make('tags')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = BelongsToMany::make('tags')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }

}
