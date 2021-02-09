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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasOne;

use App\Models\Phone;
use App\Models\User;
use App\Schemas\PhoneSchema;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $phone = Phone::factory()
            ->for(User::factory())
            ->create();

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->first();

        $this->assertTrue($phone->is($actual));
    }

    public function testWithIncludePaths(): void
    {
        $phone = Phone::factory()
            ->for(User::factory())
            ->create();

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->with('user')
            ->first();

        $this->assertTrue($phone->is($actual));
        $this->assertTrue($actual->relationLoaded('user'));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(PhoneSchema::class, 'user');

        $phone = Phone::factory()
            ->for(User::factory())
            ->create();

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->first();

        $this->assertTrue($phone->is($actual));
        $this->assertTrue($actual->relationLoaded('user'));
    }

    public function testWithFilter(): void
    {
        $phone = Phone::factory(['number' => '07777123456'])
            ->for(User::factory())
            ->create();

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->filter(['number' => '7777'])
            ->first();

        $this->assertTrue($phone->is($actual));
    }

    public function testWithFilterReturnsNull(): void
    {
        $phone = Phone::factory(['number' => '07777123456'])
            ->for(User::factory())
            ->create();

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->filter(['number' => '7788'])
            ->first();

        $this->assertNull($actual);
    }

    public function testEmpty(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->repository->queryToOne($user, 'phone')->first());
    }

    /**
     * If the relation is already loaded and there are no filters, the already
     * loaded model should be returned rather than executing a fresh query.
     */
    public function testAlreadyLoaded(): void
    {
        $phone = Phone::factory()
            ->for(User::factory())
            ->create();

        $expected = $phone->user->phone;

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertEmpty($actual->getRelations());
    }

    public function testAlreadyLoadedWithIncludePaths(): void
    {
        $phone = Phone::factory()
            ->for(User::factory())
            ->create();

        $expected = $phone->user->phone;

        $this->assertFalse($expected->relationLoaded('user'));

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->with('user')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertTrue($actual->relationLoaded('user'));
        $this->assertTrue($phone->user->is($actual->user));
    }

    /**
     * If a filter is used when the relation is already loaded, we do need to
     * execute a database query to determine if the model matches the filters.
     */
    public function testAlreadyLoadedWithFilter(): void
    {
        $phone = Phone::factory(['number' => '07777123456'])
            ->for(User::factory())
            ->create();

        $expected = $phone->user->phone;

        $actual = $this->repository
            ->queryToOne($phone->user, 'phone')
            ->filter(['number' => '7777'])
            ->first();

        $this->assertNotSame($expected, $actual);
        $this->assertTrue($expected->is($actual));
    }

}
