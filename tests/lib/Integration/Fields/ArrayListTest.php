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

use App\Models\Role;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class ArrayListTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = ArrayList::make('accessPermissions');

        $this->assertSame('accessPermissions', $attr->name());
        $this->assertSame('access_permissions', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['access_permissions'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
        $this->assertFalse($attr->isHidden($request));
        $this->assertTrue($attr->isNotHidden($request));
    }

    public function testColumn(): void
    {
        $attr = ArrayList::make('permissions', 'access_permissions');

        $this->assertSame('permissions', $attr->name());
        $this->assertSame('access_permissions', $attr->column());
        $this->assertSame(['access_permissions'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = ArrayList::make('permissions')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Role::query();

        $attr = ArrayList::make('permissions')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'roles.permissions', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    /**
     * @return array
     */
    public function validProvider(): array
    {
        return [
            [[]],
            [['foo', 'bar']],
            [null],
        ];
    }

    /**
     * @param $value
     * @dataProvider validProvider
     */
    public function testFill($value): void
    {
        $model = new Role();
        $attr = ArrayList::make('permissions');

        $attr->fill($model, $value);
        $this->assertSame($value, $model->permissions);
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
            ['foo'],
            [''],
            [new \DateTime()],
            [['foo' => 'bar', 'baz' => 'bat']],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testFillWithInvalid($value): void
    {
        $model = new Role();
        $attr = ArrayList::make('permissions');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Role();
        $attr = ArrayList::make('accessPermissions');

        $attr->fill($model, ['foo']);
        $this->assertArrayNotHasKey('access_permissions', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Role();
        $attr = ArrayList::make('accessPermissions')->unguarded();

        $attr->fill($model, ['foo']);
        $this->assertSame(['foo'], $model->access_permissions);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Role();
        $attr = ArrayList::make('permissions')->deserializeUsing(
            fn($value) => collect($value)
                ->map(fn($v) => strtoupper($v))
                ->all()
        );

        $attr->fill($model, ['foo', 'bar']);
        $this->assertSame(['FOO', 'BAR'], $model->permissions);
    }

    public function testFillUsing(): void
    {
        $role = new Role();
        $attr = ArrayList::make('permissions')->fillUsing(function ($model, $column, $value) use ($role) {
            $this->assertSame($role, $model);
            $this->assertSame('permissions', $column);
            $this->assertSame(['foo'], $value);
            $model->permissions = ['foo', 'bar'];
        });

        $attr->fill($role, ['foo']);
        $this->assertSame(['foo', 'bar'], $role->permissions);
    }

    public function testSorted(): void
    {
        $model = new Role();
        $attr = ArrayList::make('permissions')->sorted();

        $attr->fill($model, ['foo', 'bar']);
        $this->assertSame(['bar', 'foo'], $model->permissions);
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = ArrayList::make('permissions')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = ArrayList::make('permissions')->readOnly(
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

        $attr = ArrayList::make('permissions')->readOnlyOnCreate();

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

        $attr = ArrayList::make('permissions')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    /**
     * @return array
     */
    public function serializeProvider(): array
    {
        return [
            [null, null],
            [[], []],
            [['foo', 'bar'], ['foo', 'bar']],
            [['foo' => 'bar', 'baz' => 'bat'], ['bar', 'bat']],
            [[0 => 'foo', 2 => 'bar', 3 => 'bat'], ['foo', 'bar', 'bat']],
        ];
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider serializeProvider
     */
    public function testSerialize($value, $expected): void
    {
        $model = new Role(['permissions' => $value]);

        $attr = ArrayList::make('permissions');

        $this->assertSame($expected, $attr->serialize($model));
    }

    public function testSerializeUsing(): void
    {
        $model = new Role(['permissions' => ['foo', 'bar']]);

        $attr = ArrayList::make('permissions')->serializeUsing(function ($value) {
            $this->assertSame(['foo', 'bar'], $value);
            return [0 => 'baz', 2 => 'bat'];
        });

        $this->assertSame(['baz', 'bat'], $attr->serialize($model));
    }

    public function testSerializeSorted(): void
    {
        $model = new Role(['permissions' => ['foo', 'bar']]);

        $attr = ArrayList::make('permissions')->sorted();

        $this->assertSame(['bar', 'foo'], $attr->serialize($model));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = ArrayList::make('permissions')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = ArrayList::make('permissions')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }

}
