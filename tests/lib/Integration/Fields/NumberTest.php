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

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class NumberTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = Number::make('failureCount');

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
        $attr = Number::make('failures', 'failure_count');

        $this->assertSame('failures', $attr->name());
        $this->assertSame('failure_count', $attr->column());
        $this->assertSame(['failure_count'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = Number::make('failures')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = Number::make('failures')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'posts.failures', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    /**
     * @return array
     */
    public function validProvider(): array
    {
        return [
            [1],
            [0.1],
            [null],
        ];
    }

    /**
     * @param $value
     * @dataProvider validProvider
     */
    public function testFill($value): void
    {
        $model = new Post();
        $attr = Number::make('title');

        $result = $attr->fill($model, $value, []);

        $this->assertNull($result);
        $this->assertSame($value, $model->title);
    }

    /**
     * @return array
     */
    public function invalidProvider(): array
    {
        return [
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
        $attr = Number::make('title');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Post();
        $attr = Number::make('views');

        $attr->fill($model, 200, []);
        $this->assertArrayNotHasKey('views', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Post();
        $attr = Number::make('views')->unguarded();

        $attr->fill($model, 200, []);
        $this->assertSame(200, $model->views);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Post();
        $attr = Number::make('title')->deserializeUsing(
            fn($value) => $value + 200
        );

        $attr->fill($model, 100, []);
        $this->assertSame(300, $model->title);
    }

    public function testFillUsing(): void
    {
        $post = new Post();
        $attr = Number::make('views')->fillUsing(function ($model, $column, $value) use ($post) {
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

        $attr = Number::make('views')->on('profile')->unguarded();

        $attr->fill($user, 99, []);

        $this->assertSame(99, $user->profile->views);
        $this->assertSame('profile', $attr->with());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Number::make('views')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Number::make('views')->readOnly(
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

        $attr = Number::make('views')->readOnlyOnCreate();

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

        $attr = Number::make('views')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testSerialize(): void
    {
        $post = new Post();
        $attr = Number::make('views');

        $this->assertNull($attr->serialize($post));
        $post->views = 101;
        $this->assertSame(101, $attr->serialize($post));
    }

    public function testSerializeUsing(): void
    {
        $post = new Post();
        $post->views = 100;

        $attr = Number::make('views')->serializeUsing(
            fn($value) => $value * 2
        );

        $this->assertSame(200, $attr->serialize($post));
    }

    public function testSerializeRelated(): void
    {
        $user = new User();

        $attr = Number::make('views')->on('profile');

        $this->assertNull($attr->serialize($user));

        $user->profile->views = 99;

        $this->assertSame(99, $attr->serialize($user));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Number::make('views')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = Number::make('views')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }
}
