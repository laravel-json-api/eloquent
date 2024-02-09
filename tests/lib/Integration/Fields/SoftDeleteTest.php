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
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\SoftDelete;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class SoftDeleteTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2020-03-02 12:00:00');

        config()->set('app.timezone', 'UTC');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = SoftDelete::make('deletedAt');

        $this->assertInstanceOf(SoftDelete::class, $attr);
        $this->assertSame('deletedAt', $attr->name());
        $this->assertSame('deletedAt', $attr->serializedFieldName());
        $this->assertSame('deleted_at', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['deleted_at'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
        $this->assertFalse($attr->isHidden($request));
        $this->assertTrue($attr->isNotHidden($request));
    }

    public function testColumn(): void
    {
        $attr = SoftDelete::make('published', 'deleted_at');

        $this->assertSame('published', $attr->name());
        $this->assertSame('deleted_at', $attr->column());
        $this->assertSame(['deleted_at'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = SoftDelete::make('deletedAt')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = SoftDelete::make('deletedAt')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'posts.deleted_at', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    public function testNull(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with(
            'deleted_at', null
        );

        $attr = SoftDelete::make('deletedAt')->retainTimezone();

        $attr->fill($model, null, []);
    }

    public function testAppTimezone(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with('deleted_at', $this->callback(function ($value) {
            $this->assertInstanceOf(CarbonInterface::class, $value);
            $this->assertSame('2020-11-23 16:48:17', $value->toDateTimeString());
            $this->assertSame('UTC', $value->getTimezone()->getName());
            return true;
        }))->willReturnSelf();

        $attr = SoftDelete::make('deletedAt');

        $attr->fill($model, '2020-11-23T11:48:17.000000-05:00', []);
    }

    public function testSpecifiedTimezone(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with('deleted_at', $this->callback(function ($value) {
            $this->assertInstanceOf(CarbonInterface::class, $value);
            $this->assertSame('2020-11-23 01:48:17', $value->toDateTimeString());
            $this->assertSame('America/New_York', $value->getTimezone()->getName());
            return true;
        }))->willReturnSelf();

        $attr = SoftDelete::make('deletedAt')->useTimezone('America/New_York');

        // 10 hours ahead of New York. (5 ahead of UTC, then New York is 5 behind UTC)
        $attr->fill($model, '2020-11-23T11:48:17.000000+05:00', []);
    }

    public function testRetainTimezone(): void
    {
        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with('deleted_at', $this->callback(function ($value) {
            $this->assertInstanceOf(CarbonInterface::class, $value);
            $this->assertSame('2020-11-23 11:48:17', $value->toDateTimeString());
            $this->assertSame('-05:00', $value->getTimezone()->getName());
            return true;
        }))->willReturnSelf();

        $attr = SoftDelete::make('deletedAt')->retainTimezone();

        $attr->fill($model, '2020-11-23T11:48:17.000000-05:00', []);
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
        $attr = SoftDelete::make('deletedAt');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testBooleanIsTrue(): void
    {
        $attr = SoftDelete::make('archived', 'deleted_at')->asBoolean();

        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with('deleted_at', $this->callback(function ($value) {
            $this->assertInstanceOf(Carbon::class, $value);
            $this->assertEquals(Carbon::now(), $value);
            return true;
        }))->willReturnSelf();

        $attr->fill($model, true, []);
    }

    public function testBooleanIsFalse(): void
    {
        $attr = SoftDelete::make('archived', 'deleted_at')->asBoolean();

        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with('deleted_at', $this->callback(function ($value) {
            $this->assertNull($value);
            return true;
        }))->willReturnSelf();

        $attr->fill($model, false, []);
    }

    public function testBooleanIsNull(): void
    {
        $attr = SoftDelete::make('archived', 'deleted_at')->asBoolean();

        $model = $this->createMock(Post::class);
        $model->expects($this->once())->method('__set')->with('deleted_at', $this->callback(function ($value) {
            $this->assertNull($value);
            return true;
        }))->willReturnSelf();

        $attr->fill($model, null, []);
    }

    /**
     * @return array
     */
    public static function invalidBooleanProvider(): array
    {
        return [
            ['2020-11-23T11:48:17.000000-05:00'],
            [1],
            [1.0],
            [[]],
            [new \DateTime()],
            [''],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidBooleanProvider
     */
    public function testFillWithInvalidBoolean($value): void
    {
        $model = new Post();
        $attr = SoftDelete::make('archived', 'deleted_at')->asBoolean();

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Post();
        $attr = SoftDelete::make('deleted_at')->deserializeUsing(
            fn($value) => Carbon::parse($value)->addHour()
        );

        $attr->fill($model, '2020-11-23T11:48:17.000000Z', []);
        $this->assertEquals(Carbon::parse('2020-11-23T12:48:17.000000Z'), $model->deleted_at);
    }

    public function testFillUsing(): void
    {
        $post = new Post();
        $attr = SoftDelete::make('deletedAt')->fillUsing(function ($model, $column, $value) use ($post) {
            $this->assertSame($post, $model);
            $this->assertSame('deleted_at', $column);
            $this->assertEquals(Carbon::parse('2020-11-23T11:48:17.000000Z'), $value);
            $model->deleted_at = $value->subDay();
        });

        $attr->fill($post, '2020-11-23T11:48:17.000000Z', []);
        $this->assertSame('2020-11-22T11:48:17.000000Z', $post->deleted_at->toJSON());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = SoftDelete::make('pnulishedAt')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = SoftDelete::make('pnulishedAt')->readOnly(
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

        $attr = SoftDelete::make('deleted_at')->readOnlyOnCreate();

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

        $attr = SoftDelete::make('deletedAt')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testSerialize(): void
    {
        $model = new Post();
        $attr = SoftDelete::make('deletedAt');

        $this->assertNull($attr->serialize($model));

        $model->deleted_at = '2020-02-01 15:55:00';

        $this->assertEquals(new Carbon('2020-02-01 15:55:00'), $attr->serialize($model));
    }

    public function testSerializeUsing(): void
    {
        $model = new Post();
        $model->forceFill(['deleted_at' => '2020-02-01 15:55:00']);
        $attr = SoftDelete::make('deletedAt');

        $attr->serializeUsing(function ($value) {
            $this->assertEquals(new Carbon('2020-02-01 15:55:00'), $value);
            return $value->copy()->startOfDay();
        });

        $this->assertEquals(new Carbon('2020-02-01 00:00:00'), $attr->serialize($model));
    }

    public function testSerializeAsBoolean(): void
    {
        $model = new Post();
        $attr = SoftDelete::make('archived', 'deleted_at')->asBoolean();

        $this->assertFalse($attr->serialize($model));

        $model->deleted_at = '2020-02-01 15:55:00';

        $this->assertTrue($attr->serialize($model));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = SoftDelete::make('deletedAt')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = SoftDelete::make('deletedAt')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }
}
