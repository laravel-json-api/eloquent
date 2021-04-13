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
use Carbon\Carbon;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class DateTimeTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.timezone', 'UTC');
    }

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = DateTime::make('publishedAt');

        $this->assertInstanceOf(DateTime::class, $attr);
        $this->assertSame('publishedAt', $attr->name());
        $this->assertSame('publishedAt', $attr->serializedFieldName());
        $this->assertSame('published_at', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['published_at'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
        $this->assertFalse($attr->isHidden($request));
        $this->assertTrue($attr->isNotHidden($request));
    }

    public function testColumn(): void
    {
        $attr = DateTime::make('published', 'published_at');

        $this->assertSame('published', $attr->name());
        $this->assertSame('published_at', $attr->column());
        $this->assertSame(['published_at'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = DateTime::make('publishedAt')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = DateTime::make('publishedAt')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'posts.published_at', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    public function testNull(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('fill')->with(
            $this->equalTo(['published_at' => null])
        )->willReturnSelf();

        $attr = DateTime::make('publishedAt')->retainTimezone();

        $result = $attr->fill($model, null, []);

        $this->assertNull($result);
    }

    public function testAppTimezone(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('fill')->with($this->callback(function ($value) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey('published_at', $value);
            $this->assertSame('2020-11-23 16:48:17', $value['published_at']->toDateTimeString());
            $this->assertSame('UTC', $value['published_at']->getTimezone()->getName());
            return true;
        }))->willReturnSelf();

        $attr = DateTime::make('publishedAt');

        $attr->fill($model, '2020-11-23T11:48:17.000000-05:00', []);
    }

    public function testSpecifiedTimezone(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('fill')->with($this->callback(function ($value) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey('published_at', $value);
            $this->assertSame('2020-11-23 01:48:17', $value['published_at']->toDateTimeString());
            $this->assertSame('America/New_York', $value['published_at']->getTimezone()->getName());
            return true;
        }))->willReturnSelf();

        $attr = DateTime::make('publishedAt')->useTimezone('America/New_York');

        // 10 hours ahead of New York. (5 ahead of UTC, then New York is 5 behind UTC)
        $attr->fill($model, '2020-11-23T11:48:17.000000+05:00', []);
    }

    public function testRetainTimezone(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('fill')->with($this->callback(function ($value) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey('published_at', $value);
            $this->assertSame('2020-11-23 11:48:17', $value['published_at']->toDateTimeString());
            $this->assertSame('-05:00', $value['published_at']->getTimezone()->getName());
            return true;
        }))->willReturnSelf();

        $attr = DateTime::make('publishedAt')->retainTimezone();

        $attr->fill($model, '2020-11-23T11:48:17.000000-05:00', []);
    }

    /**
     * @return array
     */
    public function invalidProvider(): array
    {
        return [
            [true],
            [1],
            [1.0],
            [[]],
            [new \DateTime()],
            [''],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testFillWithInvalid($value): void
    {
        $model = new Post();
        $attr = DateTime::make('publishedAt');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Post();
        $attr = DateTime::make('createdAt');

        $attr->fill($model, '2020-11-23T11:48:17.238552Z', []);
        $this->assertArrayNotHasKey('created_at', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Post();
        $attr = DateTime::make('createdAt')->unguarded();

        $attr->fill($model, '2020-11-23T11:48:17.000000Z', []);
        $this->assertEquals(Carbon::parse('2020-11-23T11:48:17.000000Z'), $model->created_at);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Post();
        $attr = DateTime::make('published_at')->deserializeUsing(
            fn($value) => Carbon::parse($value)->addHour()
        );

        $attr->fill($model, '2020-11-23T11:48:17.000000Z', []);
        $this->assertEquals(Carbon::parse('2020-11-23T12:48:17.000000Z'), $model->published_at);
    }

    public function testFillUsing(): void
    {
        $post = new Post();
        $attr = DateTime::make('publishedAt')->fillUsing(function ($model, $column, $value) use ($post) {
            $this->assertSame($post, $model);
            $this->assertSame('published_at', $column);
            $this->assertEquals(Carbon::parse('2020-11-23T11:48:17.000000Z'), $value);
            $model->published_at = $value->subDay();
        });

        $attr->fill($post, '2020-11-23T11:48:17.000000Z', []);
        $this->assertSame('2020-11-22T11:48:17.000000Z', $post->published_at->toJSON());
    }

    public function testFillRelated(): void
    {
        $user = new User();

        $attr = DateTime::make('publishedAt')->on('profile')->unguarded();

        $attr->fill($user, '2020-11-23T11:48:17.000000Z', []);

        $this->assertEquals(Carbon::parse('2020-11-23T11:48:17.000000Z'), $user->profile->published_at);
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = DateTime::make('pnulishedAt')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = DateTime::make('pnulishedAt')->readOnly(
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

        $attr = DateTime::make('published_at')->readOnlyOnCreate();

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

        $attr = DateTime::make('publishedAt')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testSerialize(): void
    {
        $model = new Post();
        $attr = DateTime::make('publishedAt');

        $this->assertNull($attr->serialize($model));

        $model->published_at = '2020-02-01 15:55:00';

        $this->assertEquals(new Carbon('2020-02-01 15:55:00'), $attr->serialize($model));
    }

    public function testSerializeUsing(): void
    {
        $model = new Post(['published_at' => '2020-02-01 15:55:00']);
        $attr = DateTime::make('publishedAt');

        $attr->serializeUsing(function ($value) {
            $this->assertEquals(new Carbon('2020-02-01 15:55:00'), $value);
            return $value->copy()->startOfDay();
        });

        $this->assertEquals(new Carbon('2020-02-01 00:00:00'), $attr->serialize($model));
    }

    public function testSerializeRelated(): void
    {
        $user = new User();

        $attr = DateTime::make('createdAt')->on('profile');

        $this->assertNull($attr->serialize($user));

        $user->profile->created_at = $expected = new Carbon('2020-02-01 15:55:00');

        $this->assertEquals($expected, $attr->serialize($user));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = DateTime::make('publishedAt')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = DateTime::make('publishedAt')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }
}
