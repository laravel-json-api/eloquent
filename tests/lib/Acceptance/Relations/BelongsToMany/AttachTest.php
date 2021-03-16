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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsToMany;

use App\Models\Role;
use App\Models\User;
use App\Schemas\RoleSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        $user = User::factory()
            ->hasAttached(Role::factory()->count(3), ['approved' => false])
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $user->roles;
        $expected = Role::factory()->count(2)->create();

        $ids = $expected->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->attach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertRoles($expected, $actual);
        $this->assertSame(5, $user->roles()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(5, $user->roles_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($user->relationLoaded('roles'));

        foreach ($existing as $role) {
            $this->assertDatabaseHas('role_user', [
                'approved' => false, // we expect existing to keep their value
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        foreach ($expected as $role) {
            $this->assertDatabaseHas('role_user', [
                'approved' => true, // newly added should have the calculated value.
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $roles = Role::factory()->count(2)->create();

        $ids = $roles->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->with('users')
            ->attach($ids);

        $this->assertRoles($roles, $actual);
        $this->assertTrue($actual->every(fn(Role $role) => $role->relationLoaded('users')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(RoleSchema::class, 'users');

        $user = User::factory()->create();
        $roles = Role::factory()->count(2)->create();

        $ids = $roles->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->attach($ids);

        $this->assertRoles($roles, $actual);
        $this->assertTrue($actual->every(fn(Role $role) => $role->relationLoaded('users')));
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $roles = Role::factory()->count(2)->create();

        $user->roles()->attach($roles[1]);

        $ids = collect($roles)->push($roles[0], $roles[1])->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->attach($ids);

        $this->assertRoles($roles, $actual);
        $this->assertSame(2, $user->roles()->count());

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'approved' => false,
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }
}
