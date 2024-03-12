<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsTo;

use App\Models\Country;
use App\Models\Phone;
use App\Models\Role;
use App\Models\User;
use App\Schemas\UserSchema;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->first();

        $this->assertTrue($user->is($actual));
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->with('phone')
            ->first();

        $this->assertTrue($user->is($actual));
        $this->assertTrue($actual->relationLoaded('phone'));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(UserSchema::class, 'country');

        /** @var User $user */
        $user = User::factory()->for($country = Country::factory()->create())->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->first();

        $this->assertTrue($user->is($actual));
        $this->assertTrue($actual->relationLoaded('country'));
        $this->assertFalse($actual->relationLoaded('phone'));
    }

    public function testWithDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(UserSchema::class, 'country');

        /** @var User $user */
        $user = User::factory()->for($country = Country::factory()->create())->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->with('phone')
            ->first();

        $this->assertTrue($user->is($actual));
        $this->assertTrue($actual->relationLoaded('country'));
        $this->assertTrue($actual->relationLoaded('phone'));
    }

    public function testWithFilter(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->filter(['email' => $user->email])
            ->first();

        $this->assertTrue($user->is($actual));
    }

    public function testWithFilterReturnsNull(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->filter(['email' => 'john@example.com'])
            ->first();

        $this->assertNull($actual);
    }

    public function testEmpty(): void
    {
        $phone = Phone::factory()->create(['user_id' => null]);

        $this->assertNull($this->repository->queryToOne($phone, 'user')->first());
    }

    /**
     * If the relation is already loaded and there are no filters, the already
     * loaded model should be returned rather than executing a fresh query.
     */
    public function testAlreadyLoaded(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $expected = $phone->user;

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->first();

        $this->assertSame($expected, $actual);
        // profile is eager loaded as it is used in attributes.
        $this->assertSame(['profile'], array_keys($actual->getRelations()));
    }

    public function testAlreadyLoadedWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $expected = $phone->user;

        $this->assertFalse($expected->relationLoaded('phone'));

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->with('phone')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertTrue($actual->relationLoaded('phone'));
        $this->assertTrue($phone->is($actual->phone));
    }

    /**
     * If a filter is used when the relation is already loaded, we do need to
     * execute a database query to determine if the model matches the filters.
     */
    public function testAlreadyLoadedWithFilter(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $user]);

        $expected = $phone->user;

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->filter(['email' => $user->email])
            ->first();

        $this->assertNotSame($expected, $actual);
        $this->assertTrue($expected->is($actual));
    }

    public function testWithCount(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(2))
            ->create();

        $phone = Phone::factory()->create(['user_id' => $user]);

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->withCount('roles')
            ->first();

        $this->assertTrue($user->is($actual));
        $this->assertEquals(2, $actual->roles_count);
    }

    public function testAlreadyLoadedWithCount(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(2))
            ->create();

        $phone = Phone::factory()->create(['user_id' => $user]);

        $expected = $phone->user;

        $actual = $this->repository
            ->queryToOne($phone, 'user')
            ->withCount('roles')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertEquals(2, $actual->roles_count);
    }

}
