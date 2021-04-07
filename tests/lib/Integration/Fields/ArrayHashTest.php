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
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class ArrayHashTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = ArrayHash::make('accessPermissions');

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
        $attr = ArrayHash::make('permissions', 'access_permissions');

        $this->assertSame('permissions', $attr->name());
        $this->assertSame('access_permissions', $attr->column());
        $this->assertSame(['access_permissions'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = ArrayHash::make('permissions')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Role::query();

        $attr = ArrayHash::make('permissions')->sortable();

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
        $attr = ArrayHash::make('permissions');

        $attr->fill($model, $value, []);
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
            [['foo', 'bar']],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testFillWithInvalid($value): void
    {
        $model = new Role();
        $attr = ArrayHash::make('permissions');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Role();
        $attr = ArrayHash::make('accessPermissions');

        $attr->fill($model, ['foo' => 'bar'], []);
        $this->assertArrayNotHasKey('access_permissions', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Role();
        $attr = ArrayHash::make('accessPermissions')->unguarded();

        $attr->fill($model, ['foo' => 'bar'], []);
        $this->assertSame(['foo' => 'bar'], $model->access_permissions);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Role();
        $attr = ArrayHash::make('permissions')->deserializeUsing(
            fn($value) => collect($value)
                ->map(fn($v) => strtoupper($v))
                ->all()
        );

        $attr->fill($model, ['a' => 'foo', 'b' => 'bar'], []);
        $this->assertSame(['a' => 'FOO', 'b' => 'BAR'], $model->permissions);
    }

    public function testFillUsing(): void
    {
        $role = new Role();
        $attr = ArrayHash::make('permissions')->fillUsing(function ($model, $column, $value) use ($role) {
            $this->assertSame($role, $model);
            $this->assertSame('permissions', $column);
            $this->assertSame(['foo' => 'bar'], $value);
            $model->permissions = ['foo', 'bar'];
        });

        $attr->fill($role, ['foo' => 'bar'], []);
        $this->assertSame(['foo', 'bar'], $role->permissions);
    }

    public function testSorted(): void
    {
        $model = new Role();
        $attr = ArrayHash::make('permissions')->sorted();

        $attr->fill($model, ['bar' => 'foobar', 'foo' => 'bazbat'], []);
        $this->assertSame(['foo' => 'bazbat', 'bar' => 'foobar'], $model->permissions);
    }

    public function testSortKeys(): void
    {
        $model = new Role();
        $attr = ArrayHash::make('permissions')->sortKeys();

        $attr->fill($model, ['foo' => 'bar', 'baz' => 'bat'], []);
        $this->assertSame(['baz' => 'bat', 'foo' => 'bar'], $model->permissions);
    }

    public function testUnderscoreToCamel(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->underscoreFields()
            ->camelizeKeys();

        $attr->fill($model, $json = [
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testUnderscoreToDash(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->underscoreFields()
            ->dasherizeKeys();

        $attr->fill($model, $json = [
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testSnakeToCamel(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->snakeFields()
            ->camelizeKeys();

        $attr->fill($model, $json = [
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testSnakeToDash(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->snakeFields()
            ->dasherizeKeys();

        $attr->fill($model, $json = [
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testDashToCamel(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->dasherizeFields()
            ->camelizeKeys();

        $attr->fill($model, $json = [
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testDashToUnderscore(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->dasherizeFields()
            ->underscoreKeys();

        $attr->fill($model, $json = [
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testDashToSnake(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->dasherizeFields()
            ->snakeKeys();

        $attr->fill($model, $json = [
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testCamelToUnderscore(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->camelizeFields()
            ->underscoreKeys();

        $attr->fill($model, $json = [
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testCamelToSnake(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->camelizeFields()
            ->snakeKeys();

        $attr->fill($model, $json = [
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testCamelToDash(): void
    {
        $model = new Role();

        $attr = ArrayHash::make('permissions')
            ->camelizeFields()
            ->dasherizeKeys();

        $attr->fill($model, $json = [
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
        ], []);

        $this->assertSame([
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
        ], $model->permissions);

        $this->assertSame($json, $attr->serialize($model)->jsonSerialize());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = ArrayHash::make('permissions')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyFalse(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = ArrayHash::make('permissions')->readOnly(false);

        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithCallback(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = ArrayHash::make('permissions')->readOnly(
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

        $attr = ArrayHash::make('permissions')->readOnlyOnCreate();

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

        $attr = ArrayHash::make('permissions')->readOnlyOnUpdate();

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
            [[], null],
            [['foo' => 'bar', 'baz' => 'bat'], ['foo' => 'bar', 'baz' => 'bat']],
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

        $attr = ArrayHash::make('permissions');

        $this->assertSame($expected, $attr->serialize($model)->jsonSerialize());
    }

    public function testSerializeUsing(): void
    {
        $model = new Role(['permissions' => ['foo' => 'bar']]);

        $attr = ArrayHash::make('permissions')->serializeUsing(function ($value) {
            $this->assertSame(['foo' => 'bar'], $value);
            return ['baz' => 'bat'];
        });

        $this->assertSame(
            ['baz' => 'bat'],
            $attr->serialize($model)->jsonSerialize()
        );
    }

    public function testSerializeSorted(): void
    {
        $model = new Role(['permissions' => ['a' => 'foo', 'b' => 'bar']]);

        $attr = ArrayHash::make('permissions')->sorted();

        $this->assertSame(
            ['b' => 'bar', 'a' => 'foo'],
            $attr->serialize($model)->jsonSerialize()
        );
    }

    public function testSerializeSortedKeys(): void
    {
        $model = new Role(['permissions' => ['foo' => 'a', 'bar' => 'b']]);

        $attr = ArrayHash::make('permissions')->sortKeys();

        $this->assertSame(
            ['bar' => 'b', 'foo' => 'a'],
            $attr->serialize($model)->jsonSerialize()
        );
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = ArrayHash::make('permissions')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = ArrayHash::make('permissions')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }

}
