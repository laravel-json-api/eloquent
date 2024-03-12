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

class AssociateTest extends TestCase
{

    public function testNullToUser(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($phone, 'user')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertTrue($user->is($phone->getRelation('user')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testUserToNull(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory(['user_id' => $user])->create();

        $actual = $this->repository->modifyToOne($phone, 'user')->associate(null);

        $this->assertNull($actual);
        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertNull($phone->getRelation('user'));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => null,
        ]);
    }

    public function testUserToUser(): void
    {
        $existing = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $existing]);
        $user = User::factory()->create();

        $actual = $this->repository->modifyToOne($phone, 'user')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertTrue($user->is($phone->getRelation('user')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($phone, 'user')->with('phone')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
        $this->assertTrue($actual->relationLoaded('phone'));
        $this->assertTrue($phone->is($actual->getRelation('phone')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(UserSchema::class, 'country');

        $user = User::factory()->for($country = Country::factory()->create())->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($phone, 'user')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
        $this->assertTrue($actual->relationLoaded('country'));
        $this->assertTrue($country->is($actual->country));
        $this->assertFalse($actual->relationLoaded('phone'));
    }

    public function testWithDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(UserSchema::class, 'country');

        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($phone, 'user')->with('phone')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
        $this->assertTrue($actual->relationLoaded('country'));
        $this->assertTrue($actual->relationLoaded('phone'));
    }


    public function testWithCount(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(2))
            ->create();

        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($phone, 'user')->withCount('roles')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
        $this->assertEquals(2, $actual->roles_count);
    }
}
