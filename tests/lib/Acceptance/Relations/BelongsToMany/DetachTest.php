<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsToMany;

use App\Models\Role;
use App\Models\User;
use App\Schemas\RoleSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class DetachTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $user->roles;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->detach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertRoles($remove, $actual);
        $this->assertSame(1, $user->roles()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(1, $user->roles_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($user->relationLoaded('roles'));

        $this->assertDatabaseHas('role_user', [
            'role_id' => $keep->getKey(),
            'user_id' => $user->getKey(),
        ]);

        foreach ($remove as $role) {
            $this->assertDatabaseMissing('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $roles = clone $user->roles;

        $ids = $roles->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->with('users')
            ->detach($ids);

        $this->assertRoles($roles, $actual);
        $this->assertTrue($actual->every(fn(Role $role) => $role->relationLoaded('users')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(RoleSchema::class, 'users');

        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $roles = clone $user->roles;

        $ids = $roles->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->detach($ids);

        $this->assertRoles($roles, $actual);
        $this->assertTrue($actual->every(fn(Role $role) => $role->relationLoaded('users')));
    }
}
