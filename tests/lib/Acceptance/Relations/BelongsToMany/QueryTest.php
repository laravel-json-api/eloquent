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
