<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
