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
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class BooleanTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = Boolean::make('isActive');

        $this->assertSame('isActive', $attr->name());
        $this->assertSame('isActive', $attr->serializedFieldName());
        $this->assertSame('is_active', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['is_active'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
        $this->assertTrue($attr->isNotReadOnly($request));
        $this->assertFalse($attr->isHidden($request));
        $this->assertTrue($attr->isNotHidden($request));
    }

    public function testColumn(): void
    {
        $attr = Boolean::make('active', 'is_active');

        $this->assertSame('active', $attr->name());
        $this->assertSame('is_active', $attr->column());
        $this->assertSame(['is_active'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = Boolean::make('active')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = Boolean::make('isActive')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'asc');

        $this->assertSame(
            [['column' => 'posts.is_active', 'direction' => 'asc']],
            $query->toBase()->orders
        );
    }

    /**
     * @return array
     */
    public static function validProvider(): array
    {
        return [
            [true],
            [false],
            [null],
        ];
    }

    /**
     * @param $value
     * @dataProvider validProvider
     */
    public function testFill($value): void
    {
        $model = new User();
        $attr = Boolean::make('admin');

        $result = $attr->fill($model, $value, []);

        $this->assertNull($result);
        $this->assertSame($value, $model->admin);
    }

    /**
     * @return array
     */
    public static function invalidProvider(): array
    {
        return [
            ['foo'],
            [''],
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
        $model = new User();
        $attr = Boolean::make('admin');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value, []);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new User();
        $attr = Boolean::make('superUser');

        $attr->fill($model, true, []);
        $this->assertArrayNotHasKey('super_user', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new User();
        $attr = Boolean::make('superUser')->unguarded();

        $attr->fill($model, true, []);
        $this->assertTrue($model->super_user);
    }

    public function testDeserializeUsing(): void
    {
        $model = new User();
        $attr = Boolean::make('admin')->deserializeUsing(
            fn($value) => !$value
        );

        $attr->fill($model, true, []);
        $this->assertFalse($model->admin);
    }

    public function testFillUsing(): void
    {
        $user = new User();
        $attr = Boolean::make('admin')->fillUsing(function ($model, $column, $value) use ($user) {
            $this->assertSame($user, $model);
            $this->assertSame('admin', $column);
            $this->assertSame(true, $value);
            $model->admin = false;
        });

        $attr->fill($user, true, []);
        $this->assertFalse($user->admin);
    }

    public function testFillRelated(): void
    {
        $user = new User();

        $attr = Boolean::make('admin')->on('profile')->unguarded();

        $attr->fill($user, true, []);

        $this->assertTrue($user->profile->admin);
        $this->assertSame('profile', $attr->with());
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Boolean::make('admin')->readOnly();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isNotReadOnly($request));
    }

    public function testReadOnlyWithClosure(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Boolean::make('admin')->readOnly(
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

        $attr = Boolean::make('admin')->readOnlyOnCreate();

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

        $attr = Boolean::make('admin')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    /**
     * @return array
     */
    public static function serializeProvider(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
        ];
    }

    /**
     * @param $value
     * @dataProvider serializeProvider
     */
    public function testSerialize($value): void
    {
        $model = new User(['admin' => $value]);
        $attr = Boolean::make('admin');

        $this->assertSame($value, $attr->serialize($model));
    }

    public function testSerializeUsing(): void
    {
        $model = new User(['admin' => true]);
        $attr = Boolean::make('admin');

        $attr->serializeUsing(function ($value) {
            $this->assertTrue($value);
            return false;
        });

        $this->assertFalse($attr->serialize($model));
    }

    public function testSerializeRelated(): void
    {
        $user = new User();

        $attr = Boolean::make('admin')->on('profile');

        $this->assertNull($attr->serialize($user));

        $user->profile->admin = true;

        $this->assertTrue($attr->serialize($user));
    }

    public function testHidden(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method($this->anything());

        $attr = Boolean::make('admin')->hidden();

        $this->assertTrue($attr->isHidden($request));
    }

    public function testHiddenCallback(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $attr = Boolean::make('admin')->hidden(
            fn($request) => $request->isMethod('POST')
        );

        $this->assertTrue($attr->isHidden($mock));
    }

}
