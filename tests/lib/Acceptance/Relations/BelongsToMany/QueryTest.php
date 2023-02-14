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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsToMany;

use App\Models\Role;
use App\Models\User;
use App\Schemas\RoleSchema;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        // should be ignored.
        User::factory()
            ->has(Role::factory()->count(2))
            ->create();

        $actual = $this->repository
            ->queryToMany($user, 'roles')
            ->cursor();

        $this->assertRoles($user->roles()->get(), $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($actual), $user->roles_count);
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($user, 'roles')
            ->with('users')
            ->cursor();

        $this->assertRoles($user->roles()->get(), $actual);
        $this->assertTrue($actual->every(fn(Role $role) => $role->relationLoaded('users')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(RoleSchema::class, 'users');

        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($user, 'roles')
            ->cursor();

        $this->assertRoles($user->roles()->get(), $actual);
        $this->assertTrue($actual->every(fn(Role $role) => $role->relationLoaded('users')));
    }

    public function testWithFilter(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $roles = $user->roles()->get();

        $expected = $roles->take(2);
        $ids = $expected->map(fn (Role $role) => (string) $role->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($user, 'roles')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertRoles($expected, $actual);
    }

    public function testWithPivotFilter(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->hasAttached(Role::factory()->count(2), ['approved' => true])
            ->create();

        $expected = $user->roles()->get();

        $notApproved = Role::factory()->count(2)->create();
        $user->roles()->saveMany($notApproved, ['approved' => false]);

        $actual = $this->repository
            ->queryToMany($user, 'roles')
            ->filter(['approved' => true])
            ->cursor();

        $this->assertRoles($expected, $actual);
    }

    public function testWithSort(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $roles = $user->roles()->get();

        $expected = $roles->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($user, 'roles')
            ->sort('-id')
            ->cursor();

        $this->assertRoles($expected, $actual);
    }

}
