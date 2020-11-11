<?php
/*
 * Copyright 2020 Cloud Creativity Limited
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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ReplaceTest extends TestCase
{

    public function test(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $existing = $user->roles()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Role::factory()->create()
        );

        $actual = $this->repository->modifyToMany($user, 'roles')->replace(
            $expected->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertRoles($expected, $actual);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertSame($actual, $user->getRelation('roles'));

        foreach ($expected as $role) {
            $this->assertDatabaseHas('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        $this->assertDatabaseMissing('role_user', [
            'role_id' => $remove->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testEmpty(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $existing = $user->roles()->get();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->replace([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $user->roles()->count());

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertSame($actual, $user->getRelation('roles'));

        foreach ($existing as $role) {
            $this->assertDatabaseMissing('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $roles = Role::factory()->count(3)->create();

        $ids = $roles->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->with('users')
            ->replace($ids);

        $this->assertCount(3, $actual);
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
        $roles = Role::factory()->count(3)->create();

        $user->roles()->attach($roles[2]);

        $ids = collect($roles)->push($roles[1])->map(fn(Role $role) => [
            'type' => 'roles',
            'id' => (string) $role->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'roles')
            ->replace($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $user->roles()->count());
        $this->assertRoles($roles, $actual);
    }
}
