<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;
use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Rules\JsonNumber;
use LaravelJsonApi\Validation\Rules\JsonObject;

class MapTest extends TestCase
{

    public function test(): void
    {
        $map = Map::make('options', [
            Str::make('foo', 'option_foo'),
            Number::make('bar', 'option_bar'),
        ]);

        $this->assertSame('options', $map->name());
        $this->assertSame('options', $map->serializedFieldName());
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

        $result = $map->fill($model, null, []);

        $this->assertNull($result);

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

        $map->fill($model, null, []);

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
        ], []);

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
        ], []);
    }

    public function testFillRelated(): void
    {
        $user = new User();

        $attr = Map::make('options', [
            Str::make('foo', 'option_foo')->unguarded(),
            Number::make('bar', 'option_bar')->unguarded(),
        ])->on('profile');

        $attr->fill($user, ['foo' => 'foobar', 'bar' => 99], []);

        $this->assertSame('foobar', $user->profile->option_foo);
        $this->assertSame(99, $user->profile->option_bar);
        $this->assertSame('profile', $attr->with());
    }

    public function testFillPartialRelated(): void
    {
        $user = new User();

        $attr = Map::make('options', [
            Str::make('foo', 'option_foo')->on('profile')->unguarded(),
            Number::make('bar', 'option_bar')->unguarded(),
        ]);

        $attr->fill($user, ['foo' => 'foobar', 'bar' => 99], []);

        $this->assertSame('foobar', $user->profile->option_foo);
        $this->assertFalse($user->profile->offsetExists('option_bar'));
        $this->assertSame(99, $user->option_bar);
        $this->assertSame(['profile'], $attr->with());
    }

    public function testWithMultipleRelated(): void
    {
        $attr = Map::make('options', [
            Str::make('foo', 'option_foo')->on('profile'),
            Number::make('bar', 'option_bar'),
            Str::make('foobar', 'option_foobar')->on('other'),
            Str::make('bazbat', 'option_bazbat')->on('profile'),
            Str::make('foobaz', 'option_foobaz'),
        ]);

        $this->assertSame(['profile', 'other'], $attr->with());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Map::make('options', [])->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
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

    public function testSerialize(): void
    {
        $model = new Post();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo'),
            Number::make('bar', 'option_bar'),
        ]);

        $this->assertSame(['bar' => null, 'foo' => null], $map->serialize($model));

        $model->forceFill(['option_foo' => 'foobar', 'option_bar' => 'bazbat']);

        $this->assertSame(['bar' => 'bazbat', 'foo' => 'foobar'], $map->serialize($model));
    }

    public function testSerializeRelated(): void
    {
        $user = new User();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo'),
            Number::make('bar', 'option_bar'),
        ])->on('profile');

        $this->assertSame(['bar' => null, 'foo' => null], $map->serialize($user));

        $user->profile->forceFill(['option_foo' => 'foobar', 'option_bar' => 'bazbat']);

        $this->assertSame(['bar' => 'bazbat', 'foo' => 'foobar'], $map->serialize($user));
    }

    public function testSerializePartialRelated(): void
    {
        $user = new User();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo'),
            Number::make('bar', 'option_bar')->on('profile'),
        ]);

        $this->assertSame(['bar' => null, 'foo' => null], $map->serialize($user));

        $user->option_foo = 'foobar';
        $user->profile->option_bar = 'bazbat';

        $this->assertSame(['bar' => 'bazbat', 'foo' => 'foobar'], $map->serialize($user));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $map = Map::make('options', [Str::make('foo')])->hidden();

        $this->assertTrue($map->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $map = Map::make('options', [Str::make('foo')])
            ->hidden(fn($request) => $request->isMethod('POST'));

        $this->assertTrue($map->isHidden($mock));
    }

    public function testItIsValidatedOnCreate(): void
    {
        $request = $this->createMock(Request::class);
        $model = new \stdClass();

        $map = Map::make('options', [
            Str::make('foo', 'option_foo')
                ->creationRules(function ($r) use ($request) {
                    $this->assertSame($request, $r);
                    return ['foo1'];
                })
                ->updateRules(function ($r, $m) use ($request, $model) {
                    $this->assertSame($request, $r);
                    $this->assertSame($model, $m);
                    return ['foo2'];
                }),
            Number::make('bar', 'option_bar')
                ->creationRules(function ($r) use ($request) {
                    $this->assertSame($request, $r);
                    return ['bar1'];
                })
                ->updateRules(function ($r, $m) use ($request, $model) {
                    $this->assertSame($request, $r);
                    $this->assertSame($model, $m);
                    return ['bar2'];
                }),
        ]);

        $this->assertInstanceOf(IsValidated::class, $map);
        $this->assertEquals([
            '.' => ['array:bar,foo'],
            'foo' => ['string', 'foo1'],
            'bar' => [new JsonNumber(), 'bar1'],
        ], $map->rulesForCreation($request));
        $this->assertEquals([
            '.' => ['array:bar,foo'],
            'foo' => ['string', 'foo2'],
            'bar' => [new JsonNumber(), 'bar2'],
        ], $map->rulesForUpdate($request, $model));
    }
}
