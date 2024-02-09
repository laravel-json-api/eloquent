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
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class StrTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = Str::make('displayName');

        $this->assertSame('displayName', $attr->name());
        $this->assertSame('displayName', $attr->serializedFieldName());
        $this->assertSame('display_name', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['display_name'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
        $this->assertFalse($attr->isHidden($request));
        $this->assertTrue($attr->isNotHidden($request));
    }

    public function testColumn(): void
    {
        $attr = Str::make('name', 'display_name');

        $this->assertSame('name', $attr->name());
        $this->assertSame('display_name', $attr->column());
        $this->assertSame(['display_name'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = Str::make('name')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = Str::make('displayName')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'posts.display_name', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    /**
     * @return array
     */
    public static function validProvider(): array
    {
        return [
            ['Hello World'],
            [''],
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
        $attr = Str::make('title');

        $result = $attr->fill($model, $value, []);

        $this->assertSame($value, $model->title);
        $this->assertNull($result);
    }

    /**
     * @return array
     */
    public static function invalidProvider(): array
    {
        return [
            [true],
            [1],
            [1.0],
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
        $attr = Str::make('title');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Post();
        $attr = Str::make('displayName');

        $attr->fill($model, 'Hello World', []);
        $this->assertArrayNotHasKey('display_name', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Post();
        $attr = Str::make('displayName')->unguarded();

        $attr->fill($model, 'Hello World', []);
        $this->assertSame('Hello World', $model->display_name);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Post();
        $attr = Str::make('title')->deserializeUsing(
            fn($value) => strtoupper($value)
        );

        $attr->fill($model, 'Hello World', []);
        $this->assertSame('HELLO WORLD', $model->title);
    }

    public function testFillUsing(): void
    {
        $data = ['foo' => 'bar'];

        $post = new Post();
        $attr = Str::make('displayName')
            ->fillUsing(function ($model, $column, $value, array $validatedData) use ($post, $data) {
                $this->assertSame($post, $model);
                $this->assertSame('display_name', $column);
                $this->assertSame('Hello World', $value);
                $this->assertSame($data, $validatedData);
                $model->title = 'Hello World!!!';
            });

        $attr->fill($post, 'Hello World', $data);
        $this->assertSame('Hello World!!!', $post->title);
    }

    public function testFillRelated(): void
    {
        $user = new User();

        $attr = Str::make('description')->on('profile');

        $attr->fill($user, $expected = 'This is a description.', []);

        $this->assertEquals($expected, $user->profile->description);
        $this->assertSame('profile', $attr->with());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Str::make('title')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Str::make('title')->readOnly(
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

        $attr = Str::make('title')->readOnlyOnCreate();

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

        $attr = Str::make('title')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testSerialize(): void
    {
        $post = new Post();
        $attr = Str::make('title');

        $this->assertNull($attr->serialize($post));
        $post->title = 'Hello World';
        $this->assertSame('Hello World', $attr->serialize($post));
    }

    public function testSerializeUsing(): void
    {
        $post = new Post(['title' => 'Hello World']);

        $attr = Str::make('title')->serializeUsing(
            fn($value) => strtoupper($value)
        );

        $this->assertSame('HELLO WORLD', $attr->serialize($post));
    }

    public function testSerializeRelated(): void
    {
        $user = new User();

        $attr = Str::make('description')->on('profile');

        $this->assertNull($attr->serialize($user));

        $user->profile->description = $expected = 'This is a description.';

        $this->assertSame($expected, $attr->serialize($user));
    }

    public function testExtractUsing(): void
    {
        $post = new Post(['title' => 'Hello World!']);

        $attr = Str::make('display_name', 'title')->serializeUsing(fn($value) => strtoupper($value))->extractUsing(
            function($model, $column, $value) use ($post) {
                $this->assertSame($post, $model, 'model');
                $this->assertSame('title', $column, 'column');
                $this->assertSame('HELLO WORLD!', $value, 'value');
                return "{$value}!!";
            }
        );

        $this->assertSame('HELLO WORLD!!!', $attr->serialize($post));
    }

    public function testExtractUsingOnRelated(): void
    {
        $user = new User();
        $user->profile->description = 'This is a description.';

        $attr = Str::make('description')->on('profile')->extractUsing(function ($model, $column, $value) use ($user) {
            $this->assertSame($user, $model, 'model');
            $this->assertSame('description', $column, 'column');
            $this->assertSame('This is a description.', $value);
            return 'This is a different description.';
        });

        $this->assertSame('This is a different description.', $attr->serialize($user));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Str::make('title')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = Str::make('title')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }

}
