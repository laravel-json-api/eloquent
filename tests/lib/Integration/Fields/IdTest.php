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

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class IdTest extends TestCase
{

    public function test(): void
    {
        $id = ID::make('id_col');

        $this->assertSame('id', $id->name());
        $this->assertSame('id_col', $id->column());
        $this->assertFalse($id->isSparseField());
        $this->assertTrue($id->isSortable());
        $this->assertFalse($id->acceptsClientIds());
    }

    public function testNoColumn(): void
    {
        $id = ID::make();

        $this->assertSame('id', $id->name());
        $this->assertNull($id->column());
    }

    public function testClientIds(): void
    {
        $id = ID::make()->clientIds();

        $this->assertTrue($id->acceptsClientIds());
    }

    public function testFillUsesRouteKeyName(): void
    {
        $model = $this->getMockBuilder(Model::class)->onlyMethods(['getRouteKeyName'])->getMock();
        $model->method('getRouteKeyName')->willReturn('uuid');

        $id = ID::make();
        $id->fill($model, $expected = '5371f2e3-65cf-4004-ad71-e82ad98fb367', []);

        $this->assertSame($expected, $model->uuid);
    }

    public function testFillUsesColumn(): void
    {
        $id = ID::make('uuid');
        $id->fill($user = new User(), $expected = '5371f2e3-65cf-4004-ad71-e82ad98fb367', []);

        $this->assertSame($expected, $user->uuid);
    }

    public function testIsAlwaysReadOnlyIfNoClientIds(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method('isMethod');

        $id = ID::make();

        $this->assertTrue($id->isReadOnly($request));
        $this->assertFalse($id->isNotReadOnly($request));
    }

    public function testCreateWithClientIds(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))->method('isMethod')->with('POST')->willReturn(true);

        $id = ID::make()->clientIds();

        $this->assertFalse($id->isReadOnly($request));
        $this->assertTrue($id->isNotReadOnly($request));
    }

    public function testUpdateWithClientIds(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))->method('isMethod')->with('POST')->willReturn(false);

        $id = ID::make()->clientIds();

        $this->assertTrue($id->isReadOnly($request));
        $this->assertFalse($id->isNotReadOnly($request));
    }
}
