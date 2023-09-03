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

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\Integer;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;
use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Rules\JsonNumber;

class IntegerTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = Integer::make('failureCount');

        $this->assertSame('failureCount', $attr->name());
        $this->assertSame('failureCount', $attr->serializedFieldName());
        $this->assertSame('failure_count', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['failure_count'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
        $this->assertFalse($attr->isHidden($request));
        $this->assertTrue($attr->isNotHidden($request));
    }

    public function testColumn(): void
    {
        $attr = Integer::make('failures', 'failure_count');

        $this->assertSame('failures', $attr->name());
        $this->assertSame('failure_count', $attr->column());
        $this->assertSame(['failure_count'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = Integer::make('failures')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = Integer::make('failures')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'posts.failures', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    public function testItIsValidatedAsNumber(): void
    {
        $rule = (new JsonNumber())->onlyIntegers();
        $attr = Integer::make('views');

        $this->assertInstanceOf(IsValidated::class, $attr);
        $this->assertEquals([$rule], $attr->rulesForCreation(null));
        $this->assertEquals([$rule], $attr->rulesForUpdate(null, new \stdClass()));
    }

    public function testItIsValidatedAsNumberAllowingStrings(): void
    {
        $attr = Integer::make('views')->acceptStrings();

        $this->assertInstanceOf(IsValidated::class, $attr);
        $this->assertEquals(['numeric', 'integer'], $attr->rulesForCreation(null));
        $this->assertEquals(['numeric', 'integer'], $attr->rulesForUpdate(null, new \stdClass()));
    }

    /**
     * @return array
     */
    public function validProvider(): array
    {
        return [
            'int' => [1],
            'null' => [null],
            'zero' => [0],
        ];
    }

    /**
     * @param $value
     * @dataProvider validProvider
     */
    public function testFill($value): void
    {
        $model = new Post();
        $attr = Integer::make('title');

        $attr->fill($model, $value, []);

        $this->assertSame($value, $model->title);
    }

    /**
     * @return array
     */
    public function validWithStringProvider(): array
    {
        return array_merge($this->validProvider(), [
            'int as string' => ['1'],
            'zero as string' => ['0'],
        ]);
    }

    /**
     * @param $value
     * @dataProvider validWithStringProvider
     */
    public function testFillAcceptsStrings($value): void
    {
        $model = new Post();
        $attr = Integer::make('title')->acceptStrings();

        $attr->fill($model, $value, []);

        $this->assertSame($value, $model->title);
    }

    /**
     * @return array
     */
    public function invalidProvider(): array
    {
        return [
            [0.0],
            [1.1],
            [true],
            ['foo'],
            ['0'],
            [''],
            [[]],
            [new \DateTime()],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testFillWithInvalid($value): void
    {
        $model = new Post();
        $attr = Integer::make('count');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expecting the value of attribute count to be an integer.');

        $attr->fill($model, $value, []);
    }

    /**
     * @return array
     */
    public function invalidWhenAcceptingStringsProvider(): array
    {
        return [
            ['0.0'],
            ['1.1'],
            [true],
            ['foo'],
            [''],
            [[]],
            [new \DateTime()],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidWhenAcceptingStringsProvider
     */
    public function testFillWithInvalidWhenAcceptingStrings($value): void
    {
        $model = new Post();
        $attr = Integer::make('title')->acceptStrings();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Expecting the value of attribute title to be an integer or a numeric string that is an integer.'
        );

        $attr->fill($model, $value, []);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Post();
        $attr = Integer::make('views');

        $attr->fill($model, 200, []);
        $this->assertArrayNotHasKey('views', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Post();
        $attr = Integer::make('views')->unguarded();

        $attr->fill($model, 200, []);
        $this->assertSame(200, $model->views);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Post();
        $attr = Integer::make('title')->deserializeUsing(
            fn($value) => $value + 200
        );

        $attr->fill($model, 100, []);
        $this->assertSame(300, $model->title);
    }

    public function testFillUsing(): void
    {
        $post = new Post();
        $attr = Integer::make('views')->fillUsing(function ($model, $column, $value) use ($post) {
            $this->assertSame($post, $model);
            $this->assertSame('views', $column);
            $this->assertSame(200, $value);
            $model->views = 300;
        });

        $attr->fill($post, 200, []);
        $this->assertSame(300, $post->views);
    }

    public function testFillRelated(): void
    {
        $user = new User();

        $attr = Integer::make('views')->on('profile')->unguarded();

        $attr->fill($user, 99, []);

        $this->assertSame(99, $user->profile->views);
        $this->assertSame('profile', $attr->with());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Integer::make('views')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Integer::make('views')->readOnly(
            fn($request) => $request->wantsJson()
        );

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testReadOnlyOnCreate(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('isMethod')
            ->with('POST')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Integer::make('views')->readOnlyOnCreate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testReadOnlyOnUpdate(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('isMethod')
            ->with('PATCH')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Integer::make('views')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testSerialize(): void
    {
        $post = new Post();
        $attr = Integer::make('views');

        $this->assertNull($attr->serialize($post));
        $post->views = 101;
        $this->assertSame(101, $attr->serialize($post));
    }

    public function testSerializeUsing(): void
    {
        $post = new Post();
        $post->views = 100;

        $attr = Integer::make('views')->serializeUsing(
            fn($value) => $value * 2
        );

        $this->assertSame(200, $attr->serialize($post));
    }

    public function testSerializeRelated(): void
    {
        $user = new User();

        $attr = Integer::make('views')->on('profile');

        $this->assertNull($attr->serialize($user));

        $user->profile->views = 99;

        $this->assertSame(99, $attr->serialize($user));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Integer::make('views')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = Integer::make('views')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }
}
