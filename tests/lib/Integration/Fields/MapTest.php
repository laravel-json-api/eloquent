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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class MapTest extends TestCase
{

    public function test(): void
    {
        $map = Map::make('options', [
            Str::make('foo', 'option_foo'),
            Number::make('bar', 'option_bar'),
        ]);

        $this->assertSame('options', $map->name());
        $this->assertFalse($map->isSortable());
        $this->assertTrue($map->isSparseField());
        $this->assertSame(['option_bar', 'option_foo'], $map->columnsForField());
    }

    public function testFillWithNull(): void
    {
        $model = new Post();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo')->unguarded(),
            Number::make('bar', 'option_bar')->unguarded(),
        ]);

        $map->fill($model, null);

        $this->assertEquals([
            'option_foo' => null,
            'option_bar' => null,
        ], $model->getAttributes());
    }

    public function testFillIgnores(): void
    {
        $model = new Post();
        $model->forceFill($expected = [
            'option_foo' => 'foobar',
            'option_bar' => 123,
        ]);

        $map = Map::make('options', [
            Str::make('foo', 'option_foo')->unguarded(),
            Number::make('bar', 'option_bar')->unguarded(),
        ])->ignoreNull();

        $map->fill($model, null);

        $this->assertEquals($expected, $model->getAttributes());
    }

    public function testFillWithValues(): void
    {
        $model = new Post();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo')->unguarded(),
            Number::make('bar', 'option_bar')->unguarded(),
        ]);

        $map->fill($model, [
            'foo' => 'foobar',
            'bar' => 123,
        ]);

        $this->assertEquals([
            'option_foo' => 'foobar',
            'option_bar' => 123,
        ], $model->getAttributes());
    }

    public function testFillWithPartialAndUnrecognisedValues(): void
    {
        $model = $this->createMock(Model::class);

        $model->expects($this->once())->method('fill')->with([
            'option_bar' => 123,
        ])->willReturnSelf();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo'),
            Number::make('bar', 'option_bar'),
        ]);

        $map->fill($model, [
            'bar' => 123,
            'bazbat' => 'blah!',
        ]);
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Map::make('options', [])->readOnly(
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

        $attr = Map::make('options', [])->readOnlyOnCreate();

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

        $attr = Map::make('options', [])->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

}
