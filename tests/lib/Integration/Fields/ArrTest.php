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
use LaravelJsonApi\Eloquent\Fields\Arr;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class ArrTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = Arr::make('accessPermissions');

        $this->assertSame('accessPermissions', $attr->name());
        $this->assertSame('access_permissions', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['access_permissions'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testColumn(): void
    {
        $attr = Arr::make('permissions', 'access_permissions');

        $this->assertSame('permissions', $attr->name());
        $this->assertSame('access_permissions', $attr->column());
        $this->assertSame(['access_permissions'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = Arr::make('permissions')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Role::query();

        $attr = Arr::make('permissions')->sortable();

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
            [['foo' => 'bar', 'baz' => 'bat']],
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
        $attr = Arr::make('permissions');

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
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testFillWithInvalid($value): void
    {
        $model = new Role();
        $attr = Arr::make('permissions');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Role();
        $attr = Arr::make('accessPermissions');

        $attr->fill($model, ['foo']);
        $this->assertArrayNotHasKey('access_permissions', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Role();
        $attr = Arr::make('accessPermissions')->unguarded();

        $attr->fill($model, ['foo']);
        $this->assertSame(['foo'], $model->access_permissions);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->deserializeUsing(
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
        $attr = Arr::make('permissions')->fillUsing(function ($model, $column, $value) use ($role) {
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
        $attr = Arr::make('permissions')->sorted();

        $attr->fill($model, ['foo', 'bar']);
        $this->assertSame(['bar', 'foo'], $model->permissions);
    }

    public function testSortedWithAssoc(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->sorted();

        $attr->fill($model, ['bar' => 'foobar', 'foo' => 'bazbat']);
        $this->assertSame(['foo' => 'bazbat', 'bar' => 'foobar'], $model->permissions);
    }

    public function testSortedKeys(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->sortedKeys();

        $attr->fill($model, ['foo' => 'bar', 'baz' => 'bat']);
        $this->assertSame(['baz' => 'bat', 'foo' => 'bar'], $model->permissions);
    }

    public function testCamelize(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->camelize();

        $attr->fill($model, [
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ]);

        $this->assertSame([
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], $model->permissions);
    }

    public function testDasherize(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->dasherize();

        $attr->fill($model, [
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ]);

        $this->assertSame([
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], $model->permissions);
    }

    public function testSnake(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->snake();

        $attr->fill($model, [
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ]);

        $this->assertSame([
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], $model->permissions);
    }

    public function testUnderscore(): void
    {
        $model = new Role();
        $attr = Arr::make('permissions')->underscore();

        $attr->fill($model, [
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ]);

        $this->assertSame([
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], $model->permissions);
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Arr::make('permissions')->readOnly(
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

        $attr = Arr::make('permissions')->readOnlyOnCreate();

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

        $attr = Arr::make('permissions')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

}
